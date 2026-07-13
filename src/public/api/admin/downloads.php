<?php
/**
 * /api/admin/downloads.php  [requiere admin]
 *
 * GET  → todas las descargas (incluidas inactivas).
 * POST → mutaciones. Body JSON con "action":
 *   - create:     { action, item_key, title, description, version, size, url, sort_order }
 *   - update:     { action, id, title, description, version, size, url, sort_order }
 *   - set_active: { action, id, is_active: 0|1 }
 *
 * Seguridad: POST-only para mutar; el Bearer token actúa como protección CSRF.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = requireAdmin();
$db    = AdminDatabase::get();

// ── GET: listado completo ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $rows = $db->query(
            'SELECT id, item_key, title, description, version, size, url, is_active, sort_order
               FROM dbo.downloads ORDER BY sort_order ASC, id ASC'
        )->fetchAll();
        echo json_encode(['items' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400); echo json_encode(['error' => 'Body JSON inválido']); exit;
}

$action = $body['action'] ?? '';

// Campos comunes a create/update
function leerCampos(array $b): array {
    return [
        'title'       => trim((string) ($b['title'] ?? '')),
        'description' => trim((string) ($b['description'] ?? '')),
        'version'     => trim((string) ($b['version'] ?? '')),
        'size'        => trim((string) ($b['size'] ?? '')),
        'url'         => trim((string) ($b['url'] ?? '')),
        'sort_order'  => (int) ($b['sort_order'] ?? 1),
    ];
}

try {
    switch ($action) {

        case 'create': {
            $key = trim((string) ($body['item_key'] ?? ''));
            $c   = leerCampos($body);

            if ($key === '' || !preg_match('/^[a-z0-9\-]{1,50}$/', $key)) {
                http_response_code(400);
                echo json_encode(['error' => 'item_key inválido (minúsculas, números y guiones, máx 50)']); exit;
            }
            if ($c['title'] === '' || $c['url'] === '') {
                http_response_code(400); echo json_encode(['error' => 'title y url son obligatorios']); exit;
            }

            // item_key único — chequeo + insert (una colisión igual la frena el índice si existe)
            $stmt = $db->prepare('SELECT id FROM dbo.downloads WHERE item_key = :k');
            $stmt->execute([':k' => $key]);
            if ($stmt->fetch()) {
                http_response_code(409); echo json_encode(['error' => "Ya existe una descarga con item_key '{$key}'"]); exit;
            }

            $stmt = $db->prepare(
                'INSERT INTO dbo.downloads (item_key, title, description, version, size, url, is_active, sort_order, updated_by, updated_at)
                 VALUES (:k, :t, :d, :v, :s, :u, 1, :o, :by, GETDATE())'
            );
            $stmt->execute([':k' => $key, ':t' => $c['title'], ':d' => $c['description'],
                            ':v' => $c['version'], ':s' => $c['size'], ':u' => $c['url'],
                            ':o' => $c['sort_order'], ':by' => $admin['usr']]);
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        case 'update': {
            $id = (int) ($body['id'] ?? 0);
            $c  = leerCampos($body);

            if ($id <= 0 || $c['title'] === '' || $c['url'] === '') {
                http_response_code(400); echo json_encode(['error' => 'id, title y url son obligatorios']); exit;
            }

            $stmt = $db->prepare(
                'UPDATE dbo.downloads
                    SET title = :t, description = :d, version = :v, size = :s, url = :u,
                        sort_order = :o, updated_by = :by, updated_at = GETDATE()
                  WHERE id = :id'
            );
            $stmt->execute([':t' => $c['title'], ':d' => $c['description'], ':v' => $c['version'],
                            ':s' => $c['size'], ':u' => $c['url'], ':o' => $c['sort_order'],
                            ':by' => $admin['usr'], ':id' => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404); echo json_encode(['error' => 'Descarga no encontrada']); exit;
            }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        case 'set_active': {
            $id  = (int) ($body['id'] ?? 0);
            $act = (int) ($body['is_active'] ?? -1);

            if ($id <= 0 || !in_array($act, [0, 1], true)) {
                http_response_code(400); echo json_encode(['error' => 'id e is_active (0|1) son obligatorios']); exit;
            }

            $stmt = $db->prepare(
                'UPDATE dbo.downloads SET is_active = :a, updated_by = :by, updated_at = GETDATE() WHERE id = :id'
            );
            $stmt->execute([':a' => $act, ':by' => $admin['usr'], ':id' => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404); echo json_encode(['error' => 'Descarga no encontrada']); exit;
            }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida: create, update o set_active']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
