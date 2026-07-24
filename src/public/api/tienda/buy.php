<?php
/**
 * POST /api/tienda/buy.php  [requiere token]
 * Compra un producto de la tienda WCoin. Body: { "product_id": <webshop.products.id> }
 *
 * Entrega vía CashShopInventory — la misma bandeja de "comprado, pendiente
 * de reclamar" que usa el CashShop in-game (tecla "X"); el GameServer
 * resuelve el ítem real al reclamarlo, no hace falta tocar el inventario
 * binario del personaje. InventoryType/ProductType (83/80) confirmados
 * constantes contra producción — ver CLAUDE.md, Módulo Tienda de Ítems.
 *
 * Transacción PDO atómica (mismo patrón que account/buyvip.php):
 *   1. Leer producto (precio, índices, categoría) de webshop.products
 *   2. SELECT WCoinC con UPDLOCK + HOLDLOCK — previene doble compra simultánea
 *   3. Si balance < precio → ROLLBACK + error
 *   4. UPDATE CashShopData: descontar el precio
 *   5. INSERT CashShopInventory — entrega del ítem
 *   6. INSERT CashLog — auditoría (Amount negativo)
 *   7. COMMIT
 *   8. INSERT webshop.purchases — auditoría propia para estadísticas (qué se
 *      compró). Va DESPUÉS del commit y por una conexión separada
 *      (WebshopDatabase/webshop_user) porque Database::get() solo tiene
 *      SELECT sobre el schema webshop. Si falla, no revierte la compra —
 *      ver database/webshop_purchases_setup.sql.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/webshop_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const TIENDA_INVENTORY_TYPE = 83;
const TIENDA_PRODUCT_TYPE   = 80;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$auth = requireAuth();

$body      = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($body['product_id'] ?? 0);

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id inválido.']);
    exit;
}

$db = null;
try {
    $db = Database::get();
    $db->beginTransaction();

    // 1. Producto — precio e índices para armar la fila de CashShopInventory
    //    (+ nombre de categoría, solo para la auditoría de estadísticas del paso 8)
    $stmt = $db->prepare(
        'SELECT wp.name, wp.price_wcoin, wp.package_main_index, wp.product_base_index, wp.product_main_index,
                wc.name AS category_name
           FROM webshop.products wp
           LEFT JOIN webshop.categories wc ON wc.category_id = wp.category_id
          WHERE wp.id = ? AND wp.active = 1'
    );
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'El producto no existe o ya no está disponible.']);
        exit;
    }

    $price = (int) $product['price_wcoin'];

    // 2. Balance con lock — evita que dos compras simultáneas lean el mismo saldo
    $stmt = $db->prepare(
        'SELECT WCoinC FROM CashShopData WITH (UPDLOCK, HOLDLOCK) WHERE AccountID = ?'
    );
    $stmt->execute([$auth['usr']]);
    $row = $stmt->fetch();

    if (!$row) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'No se encontró el saldo de la cuenta.']);
        exit;
    }

    $balanceActual = (int) $row['WCoinC'];
    if ($balanceActual < $price) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode([
            'error'        => 'Saldo insuficiente.',
            'saldo_actual' => $balanceActual,
            'costo'        => $price,
        ]);
        exit;
    }

    // 3. Descontar
    $stmt = $db->prepare('UPDATE CashShopData SET WCoinC = WCoinC - ? WHERE AccountID = ?');
    $stmt->execute([$price, $auth['usr']]);

    // 4. Entrega — misma bandeja que usa el CashShop in-game
    $stmt = $db->prepare(
        'INSERT INTO CashShopInventory
            (AccountID, InventoryType, PackageMainIndex, ProductBaseIndex, ProductMainIndex, CoinValue, ProductType)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $auth['usr'],
        TIENDA_INVENTORY_TYPE,
        $product['package_main_index'],
        $product['product_base_index'],
        $product['product_main_index'],
        $price,
        TIENDA_PRODUCT_TYPE,
    ]);

    // 5. Auditoría
    $stmt = $db->prepare(
        'INSERT INTO CashLog (UserID, Amount, SentDate) VALUES (?, ?, GETDATE())'
    );
    $stmt->execute([$auth['usr'], -$price]);

    $db->commit();

    // 8. Auditoría propia para estadísticas — la compra ya se concretó (WCoin
    //    descontado + ítem entregado), así que un fallo acá no debe afectar la
    //    respuesta al jugador ni intentar revertir nada.
    try {
        $stmtStats = WebshopDatabase::get()->prepare(
            'INSERT INTO webshop.purchases (account_id, product_id, product_name, category_name, price_wcoin, purchased_at)
             VALUES (?, ?, ?, ?, ?, GETUTCDATE())'
        );
        $stmtStats->execute([
            $auth['usr'],
            $productId,
            $product['name'],
            $product['category_name'],
            $price,
        ]);
    } catch (Throwable $e) {
        // Silencioso a propósito — ver comentario arriba.
    }

    $stmt = $db->prepare('SELECT WCoinC FROM CashShopData WHERE AccountID = ?');
    $stmt->execute([$auth['usr']]);
    $nuevoBalance = (int) ($stmt->fetchColumn() ?? 0);

    echo json_encode([
        'success'       => true,
        'message'       => "¡Compraste {$product['name']}! Reclamalo desde el CashShop in-game (tecla \"X\").",
        'nuevo_balance' => $nuevoBalance,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    $payload = ['error' => 'Error al procesar la compra. Intentá de nuevo.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        $payload['debug'] = $e->getMessage();
    }
    echo json_encode($payload);
}
