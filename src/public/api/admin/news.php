<?php
/**
 * /api/admin/news.php  [requiere admin]
 *
 * GET  → todas las noticias (incluidas las no publicadas).
 * POST → mutaciones. Body JSON con "action":
 *   - create:        { action, title, body, category, summary, publish: 0|1 }
 *   - update:        { action, id, title, body, category, summary }
 *   - set_published: { action, id, is_published: 0|1 }
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
            "SELECT id, title, body, category, summary, is_published,
                    CONVERT(varchar(10), published_at, 23) AS published_at, created_by
               FROM dbo.news ORDER BY published_at DESC, id DESC"
        )->fetchAll();
        echo json_encode(['news' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
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

try {
    switch ($action) {

        case 'create': {
            $title    = trim((string) ($body['title'] ?? ''));
            $texto    = trim((string) ($body['body'] ?? ''));
            $category = trim((string) ($body['category'] ?? ''));
            $summary  = trim((string) ($body['summary'] ?? ''));
            $publish  = (int) ($body['publish'] ?? 1) === 1 ? 1 : 0;

            if ($title === '' || $texto === '' || $category === '' || $summary === '') {
                http_response_code(400);
                echo json_encode(['error' => 'title, body, category y summary son obligatorios']); exit;
            }

            $stmt = $db->prepare(
                'INSERT INTO dbo.news (title, body, category, summary, is_published, published_at, created_by)
                 VALUES (:t, :b, :c, :s, :p, GETDATE(), :by)'
            );
            $stmt->execute([':t' => $title, ':b' => $texto, ':c' => $category,
                            ':s' => $summary, ':p' => $publish, ':by' => $admin['usr']]);
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        case 'update': {
            $id       = (int) ($body['id'] ?? 0);
            $title    = trim((string) ($body['title'] ?? ''));
            $texto    = trim((string) ($body['body'] ?? ''));
            $category = trim((string) ($body['category'] ?? ''));
            $summary  = trim((string) ($body['summary'] ?? ''));

            if ($id <= 0 || $title === '' || $texto === '' || $category === '' || $summary === '') {
                http_response_code(400);
                echo json_encode(['error' => 'id, title, body, category y summary son obligatorios']); exit;
            }

            $stmt = $db->prepare(
                'UPDATE dbo.news SET title = :t, body = :b, category = :c, summary = :s WHERE id = :id'
            );
            $stmt->execute([':t' => $title, ':b' => $texto, ':c' => $category, ':s' => $summary, ':id' => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404); echo json_encode(['error' => 'Noticia no encontrada']); exit;
            }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        case 'set_published': {
            $id  = (int) ($body['id'] ?? 0);
            $pub = (int) ($body['is_published'] ?? -1);

            if ($id <= 0 || !in_array($pub, [0, 1], true)) {
                http_response_code(400); echo json_encode(['error' => 'id e is_published (0|1) son obligatorios']); exit;
            }

            $stmt = $db->prepare('UPDATE dbo.news SET is_published = :p WHERE id = :id');
            $stmt->execute([':p' => $pub, ':id' => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404); echo json_encode(['error' => 'Noticia no encontrada']); exit;
            }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => "Acción inválida: create, update o set_published"]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
