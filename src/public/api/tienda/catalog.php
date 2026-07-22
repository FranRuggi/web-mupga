<?php
/**
 * GET /api/tienda/catalog.php  (público, sin auth)
 * Catálogo activo de la tienda WCoin, agrupado por categoría.
 * Fuente: webshop.categories / webshop.products (ver tienda_import.php).
 *
 * Respuesta: { "categories": [{id,name}], "products": [{id,category_id,
 *   name,description,price_wcoin,icon_path}] }
 * icon_path es relativo a assets/img/shop/item/ (placeholder.gif si no
 * tiene ícono propio) — el frontend arma la URL completa con BASE.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/webshop_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

try {
    $db = WebshopDatabase::get();

    $categories = $db->query(
        'SELECT category_id, name FROM webshop.categories ORDER BY name'
    )->fetchAll();

    $rows = $db->query(
        'SELECT id, category_id, name, description, price_wcoin,
                icon_group, icon_index, has_icon
           FROM webshop.products
          WHERE active = 1
          ORDER BY category_id, price_wcoin'
    )->fetchAll();

    $products = array_map(function (array $r): array {
        $iconPath = $r['has_icon']
            ? sprintf('shop/item/%02d/%d.gif', (int) $r['icon_group'], (int) $r['icon_index'])
            : 'shop/item/placeholder.gif';

        return [
            'id'          => (int) $r['id'],
            'category_id' => $r['category_id'] !== null ? (int) $r['category_id'] : null,
            'name'        => $r['name'],
            'description' => $r['description'],
            'price_wcoin' => (int) $r['price_wcoin'],
            'icon_path'   => $iconPath,
        ];
    }, $rows);

    echo json_encode([
        'categories' => $categories,
        'products'   => $products,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    header('Cache-Control: no-store');
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
