<?php
/**
 * POST /api/forum/reply.php  [requiere token]
 * Body JSON: { thread_id, body }
 *
 * Mismas guardas server-side que create_thread.php (ver ForumValidation).
 * Hilo cerrado: bloquea a todos MENOS a los admins (F-08.04 — el staff puede
 * responder en hilos cerrados, ej. para dejar el cierre explicado).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$threadId = (int) ($body['thread_id'] ?? 0);
$content  = ForumValidation::sanitizeBody((string) ($body['body'] ?? ''));

if ($threadId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta thread_id.']); exit;
}
if ($err = ForumValidation::validateBody($content, ForumValidation::POST_BODY_MAX)) {
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

    $isAdmin = isAdminAccount($auth['usr']);
    $thread  = $repo->getThread($threadId);
    if (!$thread || $thread['is_deleted']) {
        http_response_code(404); echo json_encode(['error' => 'Hilo no encontrado.']); exit;
    }
    if ($thread['is_locked'] && !$isAdmin) {
        http_response_code(403); echo json_encode(['error' => 'Este hilo está cerrado.']); exit;
    }

    $charRepo    = new CharacterRepository(Database::get());
    $displayName = $charRepo->getMainCharacterName($auth['usr']);
    if ($displayName === null && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'Necesitás crear un personaje en el juego antes de publicar en el foro.']);
        exit;
    }
    $displayName = $displayName ?? $auth['usr'];

    $content = ForumValidation::neutralizeUnsafeLinks($content);
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

    $id = $repo->createPost($threadId, $content, $auth['usr'], $displayName);
    $db->commit();

    echo json_encode(['id' => $id], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    $payload = ['error' => 'No se pudo enviar la respuesta.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
