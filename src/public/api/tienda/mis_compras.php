<?php
/**
 * GET /api/tienda/mis_compras.php  [requiere token]
 * Compras pendientes de reclamar in-game (CashShopInventory de la cuenta),
 * con nombre/ícono resueltos contra el catálogo (join por product_base_index
 * + product_main_index). Una fila desaparece sola cuando el jugador la
 * reclama desde el CashShop in-game — no hay endpoint de borrado acá.
 *
 * Respuesta: { "items": [{name, icon_path, price_wcoin}] }
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();

try {
    $stmt = Database::get()->prepare(
        'SELECT wp.name, wp.icon_group, wp.icon_index, wp.has_icon, ci.CoinValue
           FROM dbo.CashShopInventory ci
           LEFT JOIN webshop.products wp
             ON wp.product_base_index = ci.ProductBaseIndex
            AND wp.product_main_index = ci.ProductMainIndex
          WHERE ci.AccountID = ?
          ORDER BY ci.BaseItemCode DESC'
    );
    $stmt->execute([$auth['usr']]);

    $items = array_map(function (array $r): array {
        $iconPath = ($r['has_icon'] ?? false)
            ? sprintf('shop/item/%02d/%d.gif', (int) $r['icon_group'], (int) $r['icon_index'])
            : 'shop/item/placeholder.gif';

        return [
            'name'        => $r['name'] ?? 'Ítem',
            'icon_path'   => $iconPath,
            'price_wcoin' => (int) $r['CoinValue'],
        ];
    }, $stmt->fetchAll());

    echo json_encode(['items' => $items], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
