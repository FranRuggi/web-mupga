<?php
/**
 * GET /api/site/news.php  (público, sin auth)
 * Noticias publicadas desde mupga_admin.dbo.news.
 * Reemplaza a /api/newsdata.php (que servía data/news.json estático).
 *
 * Respuesta: array con el mismo shape del JSON viejo:
 *   [ { title, date (YYYY-MM-DD), category, summary, content }, ... ]
 * Mapeo: body → content, published_at → date. Solo is_published = 1,
 * ordenadas por published_at DESC.
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
    // CONVERT(..., 23) → 'YYYY-MM-DD'
    $stmt = AdminDatabase::get()->query(
        'SELECT id, title, CONVERT(varchar(10), published_at, 23) AS date,
                category, summary, body, image_url
           FROM dbo.news
          WHERE is_published = 1
          ORDER BY published_at DESC, id DESC'
    );

    $news = [];
    foreach ($stmt->fetchAll() as $row) {
        $news[] = [
            'id'       => (int) $row['id'],
            'title'    => $row['title'],
            'date'     => $row['date'],
            'category' => $row['category'],
            'summary'  => $row['summary'],
            'content'  => $row['body'],
            'image'    => $row['image_url'],
        ];
    }

    echo json_encode($news, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    header('Cache-Control: no-store'); // nunca cachear errores
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
