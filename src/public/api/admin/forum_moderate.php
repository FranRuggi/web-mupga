<?php
/**
 * /api/admin/forum_moderate.php  [requiere admin]
 *
 * GET  → papelera: hilos borrados (soft delete), restaurables.
 * POST → { action, id, ... }:
 *   - pin / unpin
 *   - lock { reason? } / unlock       — el motivo queda visible en el hilo
 *   - move { category_id }            — mover hilo de categoría
 *   - delete_thread / delete_post { reason? }  — soft delete + auditoría
 *   - restore_thread / restore_post   — saca de la papelera
 *
 * Toda acción queda en forum.moderation_log (F-08.05). Para EDITAR contenido
 * ajeno el admin usa /api/forum/edit_post.php, que ya audita y marca
 * edited_by_staff.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = requireAdmin();
$repo  = new ForumRepository(ForumDatabase::get());

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        echo json_encode(['deleted_threads' => $repo->getDeletedThreads()], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';
$id     = (int) ($body['id'] ?? 0);
$reason = trim((string) ($body['reason'] ?? '')) ?: null;

if ($id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta id.']); exit;
}

try {
    switch ($action) {

        case 'pin':
        case 'unpin': {
            $ok = $repo->setPinned($id, $action === 'pin');
            if ($ok) $repo->logModeration($admin['usr'], $action, 'thread', (string) $id);
            break;
        }

        case 'lock': {
            $ok = $repo->setLocked($id, true, $reason);
            if ($ok) $repo->logModeration($admin['usr'], 'lock', 'thread', (string) $id, $reason);
            break;
        }

        case 'unlock': {
            $ok = $repo->setLocked($id, false);
            if ($ok) $repo->logModeration($admin['usr'], 'unlock', 'thread', (string) $id);
            break;
        }

        case 'move': {
            $categoryId = (int) ($body['category_id'] ?? 0);
            $category   = $categoryId > 0 ? $repo->getCategory($categoryId) : null;
            if (!$category) {
                http_response_code(400); echo json_encode(['error' => 'Categoría de destino inválida.']); exit;
            }
            $ok = $repo->moveThread($id, $categoryId);
            if ($ok) $repo->logModeration($admin['usr'], 'move', 'thread', (string) $id, 'A: ' . $category['name']);
            break;
        }

        case 'delete_thread': {
            $thread = $repo->getThread($id);
            if (!$thread || $thread['is_deleted']) { $ok = false; break; }
            $repo->logModeration($admin['usr'], 'delete_thread', 'thread', (string) $id, $reason,
                'TÍTULO: ' . $thread['title'] . "\n\n" . $thread['body']);
            $ok = $repo->softDeleteThread($id, $admin['usr']);
            break;
        }

        case 'delete_post': {
            $post = $repo->getPost($id);
            if (!$post || $post['is_deleted']) { $ok = false; break; }
            $repo->logModeration($admin['usr'], 'delete_post', 'post', (string) $id, $reason, $post['body']);
            $ok = $repo->softDeletePost($id, $admin['usr']);
            break;
        }

        case 'restore_thread': {
            $ok = $repo->restoreThread($id);
            if ($ok) $repo->logModeration($admin['usr'], 'restore_thread', 'thread', (string) $id);
            break;
        }

        case 'restore_post': {
            $ok = $repo->restorePost($id);
            if ($ok) $repo->logModeration($admin['usr'], 'restore_post', 'post', (string) $id);
            break;
        }

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
