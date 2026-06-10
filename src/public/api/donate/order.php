<?php
/**
 * POST /api/donate/order.php  [requiere token]
 *
 * Proxy seguro para crear una orden de pago en la API externa.
 * Extrae Account del JWT → nunca del body del cliente.
 * Reenvía el resto del body a PAYMENTS_API_URL/api/orders.
 *
 * Body esperado del cliente (Account es ignorado/sobrescrito):
 *   BaseCurrency        string
 *   BaseCurrencyAmount  int
 *   QuoteCurrency       string
 *   QuoteCurrencyAmount decimal
 *   PaymentProviderId   guid
 *   Email               string   (para notificaciones de la orden)
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['Message' => 'Método no permitido.', 'Details' => []]);
    exit;
}

$auth = requireAuth();

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['Message' => 'Cuerpo de la solicitud inválido.', 'Details' => []]);
    exit;
}

// Forzar Account desde el token — el cliente no puede modificarlo
$body['Account'] = $auth['usr'];

$paymentsUrl = rtrim($_ENV['PAYMENTS_API_URL'] ?? '', '/');
if (!$paymentsUrl) {
    http_response_code(503);
    echo json_encode([
        'Message' => 'El sistema de pagos no está configurado. Contactá a los administradores.',
        'Details' => [],
    ]);
    exit;
}

$ch = curl_init("{$paymentsUrl}/api/orders");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($body),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'ngrok-skip-browser-warning: true',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode === 0) {
    http_response_code(503);
    echo json_encode([
        'Message' => 'No se pudo conectar con el sistema de pagos. Intentá nuevamente más tarde.',
        'Details' => [],
    ]);
    exit;
}

http_response_code($httpCode);
echo $response;
