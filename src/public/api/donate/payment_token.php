<?php
/**
 * GET /api/donate/payment_token.php  [requiere token]
 *
 * Emite un JWT de pagos (mismo formato/secreto que usa order.php para el POST
 * de la orden) para que el browser lo adjunte en los GETs directos a la API
 * externa (/api/currencies/quote, /api/payments/providers), que requieren rol
 * Player. El token solo lleva uid/usr/role del usuario ya autenticado en el
 * sitio — no expone nada que el propio usuario no supiera de sí mismo.
 *
 * TTL corto (15 min, ver TokenService::generatePaymentJWT) — el frontend debe
 * pedir uno nuevo si empieza a recibir 401 de la API externa a mitad de sesión.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['Message' => 'Método no permitido.']);
    exit;
}

$auth = requireAuth();

echo json_encode([
    'token' => TokenService::generatePaymentJWT((int) $auth['uid'], $auth['usr']),
]);
