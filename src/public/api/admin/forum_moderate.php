<?php
/**
 * POST /api/admin/forum_moderate.php  [requiere admin]
 * Body JSON: { action: "pin"|"unpin"|"lock"|"unlock"|"delete_thread"|"delete_post", id }
 *
 * Acciones de moderación sobre contenido ajeno. Para editar contenido (no
 * solo moderar estado) el admin usa los mismos endpoints que los jugadores
 * (edit_post.php / delete_post.php de /api/forum/), que ya permiten admin
 * sobre cualquier autor — este endpoint es solo para pin/lock, que no
 * tienen equivalente de "dueño".
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';
$id     = (int) ($body['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta id.']); exit;
}

try {
    $repo = new ForumRepository(ForumDatabase::get());

    switch ($action) {
        case 'pin':   $ok = $repo->setPinned($id, true);  break;
        case 'unpin': $ok = $repo->setPinned($id, false); break;
        case 'lock':  $ok = $repo->setLocked($id, true);  break;
        case 'unlock':$ok = $repo->setLocked($id, false); break;
        case 'delete_thread': $ok = $repo->deleteThread($id); break;
        case 'delete_post':   $ok = $repo->deletePost($id);   break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida.']);
            exit;
    }

    if (!$ok) { http_response_code(404); echo json_encode(['error' => 'No encontrado.']); exit; }
    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo aplicar la acción.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
