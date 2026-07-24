<?php
/**
 * GET /api/admin/tienda_stats.php  [requiere admin]
 * Estadísticas de la Tienda WCoin: en qué gastan los jugadores y quiénes
 * gastan más. Lee de la auditoría propia (webshop.purchases, ver
 * database/webshop_purchases_setup.sql) — no del catálogo, así los números
 * no se pierden cuando se reimporta (webshop.products se trunca en cada
 * import). Solo cuenta compras hechas desde que existe esta tabla.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once SRC_ROOT . '/config/webshop_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

requireAdmin();

try {
    $db = WebshopDatabase::get();

    $resumen = $db->query(
        'SELECT COUNT(*) AS total_compras,
                ISNULL(SUM(price_wcoin), 0) AS total_wcoin,
                COUNT(DISTINCT account_id) AS compradores_unicos
           FROM webshop.purchases'
    )->fetch();

    $topItems = $db->query(
        'SELECT TOP 15 product_name, category_name,
                COUNT(*) AS unidades, SUM(price_wcoin) AS wcoin_total
           FROM webshop.purchases
          GROUP BY product_name, category_name
          ORDER BY wcoin_total DESC'
    )->fetchAll();

    $topCompradores = $db->query(
        'SELECT TOP 15 account_id, COUNT(*) AS compras, SUM(price_wcoin) AS wcoin_total
           FROM webshop.purchases
          GROUP BY account_id
          ORDER BY wcoin_total DESC'
    )->fetchAll();

    $porDia = $db->query(
        "SELECT CONVERT(VARCHAR(10), purchased_at, 120) AS fecha,
                COUNT(*) AS compras, SUM(price_wcoin) AS wcoin_total
           FROM webshop.purchases
          WHERE purchased_at >= DATEADD(DAY, -30, GETUTCDATE())
          GROUP BY CONVERT(VARCHAR(10), purchased_at, 120)
          ORDER BY fecha DESC"
    )->fetchAll();

    echo json_encode([
        'resumen'         => $resumen,
        'top_items'       => $topItems,
        'top_compradores' => $topCompradores,
        'por_dia'         => $porDia,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
