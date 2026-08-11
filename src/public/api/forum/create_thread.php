<?php
/**
 * POST /api/forum/create_thread.php  [requiere token]
 * Body JSON: { category_id, title, body }
 *
 * El autor sale siempre de la sesión (token), nunca del body. El nombre
 * mostrado es el personaje principal de la cuenta (CharacterRepository,
 * conexión de juego) resuelto una sola vez y guardado denormalizado —
 * mismo criterio que reclamos.reclamos.nick.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$categoryId = (int) ($body['category_id'] ?? 0);
$title      = trim((string) ($body['title'] ?? ''));
$content    = trim((string) ($body['body'] ?? ''));

if ($categoryId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta category_id.']); exit;
}
if ($title === '' || mb_strlen($title) > 200) {
    http_response_code(400); echo json_encode(['error' => 'El título debe tener entre 1 y 200 caracteres.']); exit;
}
if ($content === '' || mb_strlen($content) > 5000) {
    http_response_code(400); echo json_encode(['error' => 'El mensaje debe tener entre 1 y 5000 caracteres.']); exit;
}

try {
    $repo = new ForumRepository(ForumDatabase::get());

    if ($repo->isBanned($auth['usr'])) {
        $ban = $repo->getBan($auth['usr']);
        http_response_code(403);
        echo json_encode(['error' => 'Estás baneado del foro.' . ($ban['reason'] ? ' Motivo: ' . $ban['reason'] : '')]);
        exit;
    }

    $category = $repo->getCategory($categoryId);
    if (!$category) {
        http_response_code(404); echo json_encode(['error' => 'Categoría no encontrada.']); exit;
    }
    if ($category['admin_only_post'] && !isAdminAccount($auth['usr'])) {
        http_response_code(403); echo json_encode(['error' => 'Solo el staff puede publicar en esta categoría.']); exit;
    }

    $charRepo    = new CharacterRepository(Database::get());
    $displayName = $charRepo->getMainCharacterName($auth['usr']) ?? $auth['usr'];

    $id = $repo->createThread($categoryId, $title, $content, $auth['usr'], $displayName);

    echo json_encode(['id' => $id], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo crear el hilo.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
