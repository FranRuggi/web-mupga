<?php
/**
 * POST /api/admin/tienda_import.php  [requiere admin]
 * Reimporta el catálogo completo de la tienda WCoin (webshop.categories +
 * webshop.products) a partir de los 5 archivos de config del CashShop
 * in-game (multipart/form-data):
 *   server_package  → Server/CashShopPackageMuEmu.txt
 *   server_product  → Server/CashShopProductMuEmu.txt
 *   client_category → Client/IBSCategory.txt
 *   client_package  → Client/IBSPackage.txt
 *   client_product  → Client/IBSProduct.txt
 *
 * El catálogo vive en la base principal (schema webshop, no mupga_admin)
 * porque la futura compra (buy.php) necesita ser atómica con
 * CashShopData/CashShopInventory en la misma transacción — pero ESTE
 * endpoint solo toca webshop.*, así que conecta con el login de mínimo
 * privilegio webshop_user (WebshopDatabase), no con Database::get().
 * Ver database/webshop_setup.sql.
 *
 * Es un reemplazo TOTAL del catálogo (TRUNCATE + INSERT), no un merge
 * incremental — así "recargar" siempre refleja exactamente los archivos
 * subidos.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once SRC_ROOT . '/lib/CashShopImport.php';
require_once SRC_ROOT . '/config/webshop_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$admin = requireAdmin();

const TIENDA_IMPORT_FIELDS = [
    'server_package'  => 'CashShopPackageMuEmu.txt',
    'server_product'  => 'CashShopProductMuEmu.txt',
    'client_category' => 'IBSCategory.txt',
    'client_package'  => 'IBSPackage.txt',
    'client_product'  => 'IBSProduct.txt',
];

$maxSize = 2 * 1024 * 1024;
$contents = [];

foreach (TIENDA_IMPORT_FIELDS as $field => $label) {
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => "Falta el archivo \"{$label}\" (campo \"{$field}\")."]); exit;
    }
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => "{$label} supera los 2 MB — no parece ser el archivo correcto."]); exit;
    }
    $raw = file_get_contents($file['tmp_name']);
    if ($raw === false || trim($raw) === '') {
        http_response_code(400);
        echo json_encode(['error' => "{$label} está vacío."]); exit;
    }
    $contents[$field] = $raw;
}

try {
    $categories     = parseIbsCategory($contents['client_category']);
    $ibsPackages    = parseIbsPackage($contents['client_package']);
    $ibsProducts    = parseIbsProduct($contents['client_product']);
    $packagesByBase = parseCashShopPackage($contents['server_package']);
    $products       = parseCashShopProduct($contents['server_product']);

    if (empty($products)) {
        http_response_code(400);
        echo json_encode(['error' => 'CashShopProductMuEmu.txt no tiene ninguna fila reconocible.']); exit;
    }

    $iconDir = SRC_ROOT . '/public/assets/img/shop/item';
    $iconExists = function (int $group, int $index) use ($iconDir): bool {
        return is_file(sprintf('%s/%02d/%d.gif', $iconDir, $group, $index));
    };

    $catalog = buildCashShopCatalog($ibsPackages, $ibsProducts, $packagesByBase, $products, $iconExists);

    $db = WebshopDatabase::get();
    $db->beginTransaction();

    $db->exec('TRUNCATE TABLE webshop.products');
    $db->exec('TRUNCATE TABLE webshop.categories');

    $stmtCat = $db->prepare('INSERT INTO webshop.categories (category_id, name) VALUES (:id, :name)');
    foreach ($categories as $id => $name) {
        $stmtCat->execute([':id' => $id, ':name' => $name]);
    }

    $stmtProd = $db->prepare(
        'INSERT INTO webshop.products (
            product_base_index, product_main_index, category_id, package_main_index,
            name, description, price_wcoin, item_id,
            item_level, item_skill, item_luck, item_option, item_exc_opt, item_set_opt,
            item_joh, item_oex, item_socket1, item_socket2, item_socket3, item_socket4, item_socket5,
            duration_seconds, icon_group, icon_index, has_icon
        ) VALUES (
            :product_base_index, :product_main_index, :category_id, :package_main_index,
            :name, :description, :price_wcoin, :item_id,
            :item_level, :item_skill, :item_luck, :item_option, :item_exc_opt, :item_set_opt,
            :item_joh, :item_oex, :item_socket1, :item_socket2, :item_socket3, :item_socket4, :item_socket5,
            :duration_seconds, :icon_group, :icon_index, :has_icon
        )'
    );

    $missingIcons = [];
    foreach ($catalog as $row) {
        $stmtProd->execute([
            ':product_base_index' => $row['product_base_index'],
            ':product_main_index' => $row['product_main_index'],
            ':category_id'        => $row['category_id'],
            ':package_main_index' => $row['package_main_index'],
            ':name'               => $row['name'],
            ':description'        => $row['description'],
            ':price_wcoin'        => $row['price_wcoin'],
            ':item_id'            => $row['item_id'],
            ':item_level'         => $row['item_level'],
            ':item_skill'         => $row['item_skill'],
            ':item_luck'          => $row['item_luck'],
            ':item_option'        => $row['item_option'],
            ':item_exc_opt'       => $row['item_exc_opt'],
            ':item_set_opt'       => $row['item_set_opt'],
            ':item_joh'           => $row['item_joh'],
            ':item_oex'           => $row['item_oex'],
            ':item_socket1'       => $row['item_socket1'],
            ':item_socket2'       => $row['item_socket2'],
            ':item_socket3'       => $row['item_socket3'],
            ':item_socket4'       => $row['item_socket4'],
            ':item_socket5'       => $row['item_socket5'],
            ':duration_seconds'   => $row['duration_seconds'],
            ':icon_group'         => $row['icon_group'],
            ':icon_index'         => $row['icon_index'],
            ':has_icon'           => $row['has_icon'],
        ]);
        if (!$row['has_icon']) {
            $missingIcons[] = ['name' => $row['name'], 'item_id' => $row['item_id']];
        }
    }

    $db->commit();

    echo json_encode([
        'success'        => true,
        'categorias'     => count($categories),
        'productos'      => count($catalog),
        'iconos_faltantes' => $missingIcons,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    $payload = ['error' => 'Error al importar el catálogo.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        $payload['debug'] = $e->getMessage();
    }
    echo json_encode($payload);
}
