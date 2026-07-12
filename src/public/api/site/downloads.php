<?php
/**
 * GET /api/site/downloads.php  (público, sin auth)
 * Descargas activas desde mupga_admin.dbo.downloads.
 * Reemplaza a /api/downloadsdata.php (que servía data/downloads.json estático).
 *
 * Respuesta: { "items": [ { id, title, description, version, size, url }, ... ] }
 * (item_key se expone como "id" — mismo shape que el JSON estático viejo).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/admin_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

try {
    $stmt = AdminDatabase::get()->query(
        'SELECT item_key, title, description, version, size, url
           FROM dbo.downloads
          WHERE is_active = 1
          ORDER BY sort_order ASC, id ASC'
    );

    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $items[] = [
            'id'          => $row['item_key'],
            'title'       => $row['title'],
            'description' => $row['description'],
            'version'     => $row['version'],
            'size'        => $row['size'],
            'url'         => $row['url'],
        ];
    }

    echo json_encode(['items' => $items], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    header('Cache-Control: no-store'); // nunca cachear errores
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
