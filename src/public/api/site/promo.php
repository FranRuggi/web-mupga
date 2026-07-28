<?php
/**
 * GET /api/site/promo.php  (público, sin auth)
 * Popup promocional desde mupga_admin.dbo.promo_popup (fila única id=1).
 * Lo consume el frontend una vez por sesión de browser (ver loadPromoPopup()
 * en app.js) — si is_active=0 no se muestra nada.
 *
 * Sin cache: el ControlPanel lo prende/apaga y tiene que reflejarse al
 * toque (mismo criterio que site/status.php).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/admin_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

try {
    $stmt = AdminDatabase::get()->prepare(
        'SELECT is_active, eyebrow, title, highlight, description,
                image_url, cta_text, cta_link
           FROM dbo.promo_popup
          WHERE id = 1'
    );
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row || !(int) $row['is_active']) {
        echo json_encode(['is_active' => 0]);
        exit;
    }

    echo json_encode([
        'is_active'   => 1,
        'eyebrow'     => $row['eyebrow'],
        'title'       => $row['title'],
        'highlight'   => $row['highlight'],
        'description' => $row['description'],
        'image_url'   => $row['image_url'],
        'cta_text'    => $row['cta_text'],
        'cta_link'    => $row['cta_link'],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
