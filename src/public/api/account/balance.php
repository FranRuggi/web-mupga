<?php
/**
 * GET /api/account/balance.php  [requiere token]
 * Devuelve el saldo de WCoin, WCoinP y GoblinPoint.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$auth    = requireAuth();
$balance = (new CreditsRepository(Database::get()))->getBalance($auth['usr']);

if (!$balance) {
    echo json_encode(['WCoinC' => 0, 'WCoinP' => 0, 'GoblinPoint' => 0]);
    exit;
}

echo json_encode([
    'WCoinC'      => (int) $balance['WCoinC'],
    'WCoinP'      => (int) $balance['WCoinP'],
    'GoblinPoint' => (int) $balance['GoblinPoint'],
], JSON_THROW_ON_ERROR);
