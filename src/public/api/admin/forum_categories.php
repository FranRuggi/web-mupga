<?php
/**
 * POST /api/admin/forum_categories.php  [requiere admin]
 * Body JSON con "action":
 *   - create: { action, name, description?, sort_order?, admin_only_post? }
 *   - update: { action, id, name, description?, sort_order?, admin_only_post? }
 *   - delete: { action, id }
 *
 * El listado se lee del endpoint público GET /api/forum/categories.php
 * (mismos datos, no hace falta duplicarlo acá).
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

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'categoria';
}

try {
    $repo = new ForumRepository(ForumDatabase::get());

    switch ($action) {

        case 'create': {
            $name        = trim((string) ($body['name'] ?? ''));
            $description = trim((string) ($body['description'] ?? '')) ?: null;
            $sortOrder   = (int) ($body['sort_order'] ?? 0);
            $adminOnly   = !empty($body['admin_only_post']);
            $isHidden    = !empty($body['is_hidden']);

            if ($name === '' || mb_strlen($name) > 100) {
                http_response_code(400); echo json_encode(['error' => 'El nombre debe tener entre 1 y 100 caracteres.']); exit;
            }

            $id = $repo->createCategory($name, slugify($name) . '-' . bin2hex(random_bytes(2)), $description, $sortOrder, $adminOnly, $isHidden);
            echo json_encode(['success' => true, 'id' => $id], JSON_THROW_ON_ERROR);
            break;
        }

        case 'update': {
            $id          = (int) ($body['id'] ?? 0);
            $name        = trim((string) ($body['name'] ?? ''));
            $description = trim((string) ($body['description'] ?? '')) ?: null;
            $sortOrder   = (int) ($body['sort_order'] ?? 0);
            $adminOnly   = !empty($body['admin_only_post']);
            $isHidden    = !empty($body['is_hidden']);

            if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Falta id.']); exit; }
            if ($name === '' || mb_strlen($name) > 100) {
                http_response_code(400); echo json_encode(['error' => 'El nombre debe tener entre 1 y 100 caracteres.']); exit;
            }

            $ok = $repo->updateCategory($id, $name, $description, $sortOrder, $adminOnly, $isHidden);
            if (!$ok) { http_response_code(404); echo json_encode(['error' => 'Categoría no encontrada.']); exit; }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        case 'delete': {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Falta id.']); exit; }

            if ($repo->categoryHasThreads($id)) {
                http_response_code(409);
                echo json_encode(['error' => 'La categoría tiene hilos — borralos o moverlos primero.']);
                exit;
            }

            $ok = $repo->deleteCategory($id);
            if (!$ok) { http_response_code(404); echo json_encode(['error' => 'Categoría no encontrada.']); exit; }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida: create, update o delete']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al procesar la categoría.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
