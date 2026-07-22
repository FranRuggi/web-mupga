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
 *   1. Leer producto (precio, índices) de webshop.products
 *   2. SELECT WCoinC con UPDLOCK + HOLDLOCK — previene doble compra simultánea
 *   3. Si balance < precio → ROLLBACK + error
 *   4. UPDATE CashShopData: descontar el precio
 *   5. INSERT CashShopInventory — entrega del ítem
 *   6. INSERT CashLog — auditoría (Amount negativo)
 *   7. COMMIT
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
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
    $stmt = $db->prepare(
        'SELECT price_wcoin, package_main_index, product_base_index, product_main_index
           FROM webshop.products WHERE id = ? AND active = 1'
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

    $stmt = $db->prepare('SELECT WCoinC FROM CashShopData WHERE AccountID = ?');
    $stmt->execute([$auth['usr']]);
    $nuevoBalance = (int) ($stmt->fetchColumn() ?? 0);

    echo json_encode([
        'success'       => true,
        'message'       => 'Compra realizada. Reclamalo desde el CashShop in-game (tecla "X").',
        'nuevo_balance' => $nuevoBalance,
    ], JSON_THROW_ON_ERROR);

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
