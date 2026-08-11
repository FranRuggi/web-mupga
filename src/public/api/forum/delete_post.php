<?php
/**
 * POST /api/forum/delete_post.php  [requiere token]
 * Body JSON: { target_type: "thread"|"post", id }
 *
 * Dueño del contenido o admin. Borrar un hilo borra también todas sus
 * respuestas (ON DELETE CASCADE en forum.posts).
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

$targetType = (string) ($body['target_type'] ?? '');
$id         = (int) ($body['id'] ?? 0);

if (!in_array($targetType, ['thread', 'post'], true) || $id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Parámetros inválidos.']); exit;
}

try {
    $repo = new ForumRepository(ForumDatabase::get());

    if ($targetType === 'thread') {
        $thread = $repo->getThread($id);
        if (!$thread) { http_response_code(404); echo json_encode(['error' => 'Hilo no encontrado.']); exit; }

        if ($thread['author_account'] !== $auth['usr'] && !isAdminAccount($auth['usr'])) {
            http_response_code(403); echo json_encode(['error' => 'No podés borrar este hilo.']); exit;
        }

        $repo->deleteThread($id);
    } else {
        $post = $repo->getPost($id);
        if (!$post) { http_response_code(404); echo json_encode(['error' => 'Mensaje no encontrado.']); exit; }

        if ($post['author_account'] !== $auth['usr'] && !isAdminAccount($auth['usr'])) {
            http_response_code(403); echo json_encode(['error' => 'No podés borrar este mensaje.']); exit;
        }

        $repo->deletePost($id);
    }

    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo borrar.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
