<?php
/**
 * POST /api/forum/reply.php  [requiere token]
 * Body JSON: { thread_id, body }
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

$threadId = (int) ($body['thread_id'] ?? 0);
$content  = trim((string) ($body['body'] ?? ''));

if ($threadId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta thread_id.']); exit;
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

    $thread = $repo->getThread($threadId);
    if (!$thread) {
        http_response_code(404); echo json_encode(['error' => 'Hilo no encontrado.']); exit;
    }
    if ($thread['is_locked']) {
        http_response_code(403); echo json_encode(['error' => 'Este hilo está cerrado.']); exit;
    }

    $charRepo    = new CharacterRepository(Database::get());
    $displayName = $charRepo->getMainCharacterName($auth['usr']) ?? $auth['usr'];

    $id = $repo->createPost($threadId, $content, $auth['usr'], $displayName);

    echo json_encode(['id' => $id], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo enviar la respuesta.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
