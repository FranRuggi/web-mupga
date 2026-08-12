<?php
/**
 * POST /api/forum/follow.php  [requiere token]
 * Body JSON: { thread_id, follow: true|false }
 * Seguir / dejar de seguir un hilo (F-07.01). Dejar de seguir corta los
 * avisos aunque el usuario siga respondiendo gente en el hilo.
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
$follow   = (bool) ($body['follow'] ?? false);

if ($threadId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta thread_id.']); exit;
}

try {
    $repo   = new ForumRepository(ForumDatabase::get());
    $thread = $repo->getThread($threadId);
    if (!$thread || $thread['is_deleted']) {
        http_response_code(404); echo json_encode(['error' => 'Hilo no encontrado.']); exit;
    }

    if ($follow) {
        $repo->followThread($auth['usr'], $threadId);
    } else {
        $repo->unfollowThread($auth['usr'], $threadId);
    }

    echo json_encode(['following' => $follow], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo actualizar la suscripción.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
