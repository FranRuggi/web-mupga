<?php
/**
 * POST /api/account/buyvip.php  [requiere token]
 * Compra VIP Oro 15 días descontando 5.000 WCoins de la cuenta.
 *
 * Seguridad:
 *   - Solo POST. El Bearer token actúa como protección CSRF (no se envía
 *     automáticamente por el navegador, a diferencia de las cookies).
 *   - AccountID tomado exclusivamente de la sesión; se ignora cualquier valor
 *     proveniente del cliente.
 *
 * Transacción PDO atómica:
 *   1. SELECT WCoinC con UPDLOCK + HOLDLOCK — previene race condition
 *   2. Si balance < 5.000 → ROLLBACK + error
 *   3. UPDATE CashShopData: descontar 5.000 WCoins
 *   4. EXEC sp_SetAccountGOLDVIP — activar VIP Oro por 15 días
 *   5. INSERT CashLog — registro de auditoría
 *   6. COMMIT
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$auth = requireAuth();

$vipCost = 5000;
$vipDays = 15;

$db = null;
try {
    $db = Database::get();
    $db->beginTransaction();

    // 1. Leer balance con lock para evitar doble compra simultánea
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
    if ($balanceActual < $vipCost) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode([
            'error'        => 'Saldo insuficiente. Necesitás 5.000 WCoins para activar el VIP.',
            'saldo_actual' => $balanceActual,
            'costo'        => $vipCost,
        ]);
        exit;
    }

    // 2. Descontar WCoins del balance de la cuenta
    $stmt = $db->prepare(
        'UPDATE CashShopData SET WCoinC = WCoinC - ? WHERE AccountID = ?'
    );
    $stmt->execute([$vipCost, $auth['usr']]);

    // 3. Activar VIP Gold — el SP suma días a la fecha existente
    $stmt = $db->prepare('EXEC sp_SetAccountGOLDVIP ?, ?');
    $stmt->execute([$auth['usr'], $vipDays]);
    do {} while ($stmt->nextRowset());

    // 4. Registrar operación en CashLog para auditoría (Amount negativo = débito)
    $stmt = $db->prepare(
        'INSERT INTO CashLog (UserID, Amount, SentDate) VALUES (?, ?, GETDATE())'
    );
    $stmt->execute([$auth['usr'], -$vipCost]);

    $db->commit();

    // Leer el nuevo balance y la fecha de expiración actualizada
    $stmt = $db->prepare('SELECT WCoinC FROM CashShopData WHERE AccountID = ?');
    $stmt->execute([$auth['usr']]);
    $nuevoBalance = (int) ($stmt->fetchColumn() ?? 0);

    $stmt = $db->prepare('SELECT AccountExpireDate FROM MEMB_INFO WHERE memb___id = ?');
    $stmt->execute([$auth['usr']]);
    $vipExpira = $stmt->fetchColumn() ?: null;

    echo json_encode([
        'success'       => true,
        'message'       => '¡VIP Oro activado! Tu suscripción está activa por ' . $vipDays . ' días.',
        'nuevo_balance' => $nuevoBalance,
        'vip_expira'    => $vipExpira,
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
