<?php
/**
 * POST /api/forum/create_thread.php  [requiere token]
 * Body JSON: { category_id, title, body }
 *
 * El autor sale siempre de la sesión (token), nunca del body. El nombre
 * mostrado es el personaje principal de la cuenta (CharacterRepository,
 * conexión de juego) resuelto una sola vez y guardado denormalizado —
 * mismo criterio que reclamos.reclamos.nick.
 *
 * Guardas server-side (ver ForumValidation):
 * - Validación de límites y sanitizado (422 si falla) — F-01.01
 * - Links con esquema no-http neutralizados; cuentas nuevas (<5 posts) con
 *   links externos fuera de whitelist des-linkificados — F-01.02 / F-09.03
 * - Cuenta sin personaje no puede publicar (sí leer) — F-09.02
 * - Antiflood por cuenta (cooldown 30s + máx 10/hora), admins exentos,
 *   chequeo transaccional con UPDLOCK/HOLDLOCK — F-09.01
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once SRC_ROOT . '/lib/ForumNotify.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$categoryId = (int) ($body['category_id'] ?? 0);
$title      = trim(ForumValidation::sanitizeBody((string) ($body['title'] ?? '')));
$content    = ForumValidation::sanitizeBody((string) ($body['body'] ?? ''));

if ($categoryId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta category_id.']); exit;
}
if ($err = ForumValidation::validateTitle($title)) {
    http_response_code(422); echo json_encode(['error' => $err]); exit;
}
if ($err = ForumValidation::validateBody($content, ForumValidation::THREAD_BODY_MAX)) {
    http_response_code(422); echo json_encode(['error' => $err]); exit;
}

$db = null;
try {
    $db   = ForumDatabase::get();
    $repo = new ForumRepository($db);

    if ($ban = $repo->getBan($auth['usr'])) {
        http_response_code(403);
        $hasta = $ban['expires_at'] ? ' Hasta: ' . substr($ban['expires_at'], 0, 16) . ' UTC.' : '';
        echo json_encode(['error' => 'Estás baneado del foro.' . ($ban['reason'] ? ' Motivo: ' . $ban['reason'] . '.' : '') . $hasta]);
        exit;
    }

    $isAdmin  = isAdminAccount($auth['usr']);
    $category = $repo->getCategory($categoryId);
    if (!$category || ($category['is_hidden'] && !$isAdmin)) {
        http_response_code(404); echo json_encode(['error' => 'Categoría no encontrada.']); exit;
    }
    if ($category['admin_only_post'] && !$isAdmin) {
        http_response_code(403); echo json_encode(['error' => 'Solo el staff puede publicar en esta categoría.']); exit;
    }

    // F-09.02: sin personaje no se publica (leer sí puede). También evita
    // exponer la cuenta de login como nombre visible.
    $charRepo    = new CharacterRepository(Database::get());
    $displayName = $charRepo->getMainCharacterName($auth['usr']);
    if ($displayName === null && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'Necesitás crear un personaje en el juego antes de publicar en el foro.']);
        exit;
    }
    $displayName = $displayName ?? $auth['usr'];

    // Saneamiento de links e imágenes, siempre después de validar longitud
    $content = ForumValidation::neutralizeUnsafeLinks($content);
    $content = ForumValidation::restrictImages($content);
    if (!$isAdmin && $repo->countPostsByAccount($auth['usr']) < ForumValidation::NEW_ACCOUNT_LINK_THRESHOLD) {
        $content = ForumValidation::restrictExternalLinks($content);
    }

    $db->beginTransaction();

    if (!$isAdmin) {
        $throttle = $repo->getPostingThrottle($auth['usr']);
        if ($throttle['seconds_since_last'] !== null
            && $throttle['seconds_since_last'] < ForumValidation::POST_COOLDOWN_SECONDS) {
            $db->rollBack();
            $restante = ForumValidation::POST_COOLDOWN_SECONDS - $throttle['seconds_since_last'];
            http_response_code(429);
            echo json_encode(['error' => "Esperá {$restante} segundos antes de publicar de nuevo.", 'retry_in_seconds' => $restante]);
            exit;
        }
        if ($throttle['count_last_hour'] >= ForumValidation::POSTS_PER_HOUR_MAX) {
            $db->rollBack();
            http_response_code(429);
            echo json_encode(['error' => 'Llegaste al límite de publicaciones por hora. Probá más tarde.']);
            exit;
        }
    }

    $id = $repo->createThread($categoryId, $title, $content, $auth['usr'], $displayName);
    $db->commit();

    // Auto-follow + avisos de menciones — si fallan, el hilo ya está publicado
    try {
        ForumNotify::afterNewThread($repo, $id, $auth['usr'], $displayName, $content);
    } catch (Throwable $e) { /* nunca revierte la publicación */ }

    echo json_encode(['id' => $id], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    $payload = ['error' => 'No se pudo crear el hilo.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
