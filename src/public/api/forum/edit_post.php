<?php
/**
 * POST /api/forum/edit_post.php  [requiere token]
 * Body JSON: { target_type: "thread"|"post", id, title? (solo thread), body }
 *
 * Dueño del contenido o admin. El admin puede editar cualquier publicación
 * (moderación de contenido) — el resto solo la propia.
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
$content    = trim((string) ($body['body'] ?? ''));

if (!in_array($targetType, ['thread', 'post'], true) || $id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Parámetros inválidos.']); exit;
}
if ($content === '' || mb_strlen($content) > 5000) {
    http_response_code(400); echo json_encode(['error' => 'El mensaje debe tener entre 1 y 5000 caracteres.']); exit;
}

try {
    $repo = new ForumRepository(ForumDatabase::get());

    if ($targetType === 'thread') {
        $title = trim((string) ($body['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 200) {
            http_response_code(400); echo json_encode(['error' => 'El título debe tener entre 1 y 200 caracteres.']); exit;
        }

        $thread = $repo->getThread($id);
        if (!$thread) { http_response_code(404); echo json_encode(['error' => 'Hilo no encontrado.']); exit; }

        if ($thread['author_account'] !== $auth['usr'] && !isAdminAccount($auth['usr'])) {
            http_response_code(403); echo json_encode(['error' => 'No podés editar este hilo.']); exit;
        }

        $repo->editThread($id, $title, $content);
    } else {
        $post = $repo->getPost($id);
        if (!$post) { http_response_code(404); echo json_encode(['error' => 'Mensaje no encontrado.']); exit; }

        if ($post['author_account'] !== $auth['usr'] && !isAdminAccount($auth['usr'])) {
            http_response_code(403); echo json_encode(['error' => 'No podés editar este mensaje.']); exit;
        }

        $repo->editPost($id, $content);
    }

    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo editar.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
