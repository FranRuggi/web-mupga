<?php
/**
 * POST /api/admin/forum_categories.php  [requiere admin]
 * Body JSON con "action":
 *   - create: { action, name, description?, sort_order?, admin_only_post? }
 *   - update: { action, id, name, description?, sort_order?, admin_only_post? }
 *   - delete: { action, id, move_to? , force? }
 *
 * Borrar una categoría con contenido adentro exige decir qué hacer con él:
 * `move_to` reasigna los hilos a otra categoría (no se pierde nada) o `force`
 * borra todo en cascada. Sin ninguno de los dos responde 409 con los conteos.
 * Toda variante queda en forum.moderation_log.
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

$admin = requireAdmin();

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

            $categoria = $repo->getCategory($id);
            if (!$categoria) { http_response_code(404); echo json_encode(['error' => 'Categoría no encontrada.']); exit; }

            $counts = $repo->getCategoryContentCounts($id);
            $moveTo = (int) ($body['move_to'] ?? 0);
            $force  = !empty($body['force']);

            // Con contenido adentro hace falta decir explícitamente qué hacer con
            // él. Los conteos viajan en el 409 para que el panel pueda ofrecer las
            // dos salidas sin adivinar (mover los hilos, o borrar todo).
            if ($counts['total'] > 0 && $moveTo <= 0 && !$force) {
                http_response_code(409);
                echo json_encode([
                    'error'  => 'La categoría tiene ' . $counts['total'] . ' hilo(s) — '
                              . $counts['visible'] . ' visibles y ' . $counts['deleted']
                              . ' en la papelera. Movelos a otra categoría o confirmá el borrado total.',
                    'counts' => $counts,
                ], JSON_THROW_ON_ERROR);
                exit;
            }

            if ($moveTo > 0) {
                if ($moveTo === $id) {
                    http_response_code(400); echo json_encode(['error' => 'La categoría destino no puede ser la misma.']); exit;
                }
                if (!$repo->getCategory($moveTo)) {
                    http_response_code(404); echo json_encode(['error' => 'La categoría destino no existe.']); exit;
                }

                $movidos = $repo->moveAllThreads($id, $moveTo);
                $repo->deleteCategory($id);
                $repo->logModeration($admin['usr'], 'delete_category', 'category', (string) $id,
                    'Borrada "' . $categoria['name'] . '" — ' . $movidos . ' hilo(s) movidos a la categoría ' . $moveTo);

                echo json_encode(['success' => true, 'moved' => $movidos], JSON_THROW_ON_ERROR);
                break;
            }

            if ($force && $counts['total'] > 0) {
                // Cascada real: única excepción al soft delete del módulo (ver
                // ForumRepository::purgeCategory). El log de auditoría sobrevive.
                $borrado = $repo->purgeCategory($id);
                $repo->logModeration($admin['usr'], 'delete_category', 'category', (string) $id,
                    'Borrada "' . $categoria['name'] . '" con todo su contenido — '
                    . $borrado['threads'] . ' hilo(s) y ' . $borrado['posts'] . ' respuesta(s)');

                echo json_encode(['success' => true, 'purged' => $borrado], JSON_THROW_ON_ERROR);
                break;
            }

            $repo->deleteCategory($id);
            $repo->logModeration($admin['usr'], 'delete_category', 'category', (string) $id,
                'Borrada "' . $categoria['name'] . '" (vacía)');
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
