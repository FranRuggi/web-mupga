<?php
/**
 * POST /api/donate/promotion_order.php  [requiere token]
 *
 * Proxy seguro para crear una orden a partir de una promoción en la API externa.
 * Extrae Account del JWT → nunca del body del cliente. Mismo patrón que order.php
 * (ver .claude/docs/payment_integration.md, Paso 6).
 *
 * Body esperado del cliente:
 *   promotionId        guid   (arma la URL, no se reenvía en el body)
 *   paymentProviderId  guid
 *   userEmail          string
 *
 * Reenvía a PAYMENTS_API_URL/api/promotions/{promotionId}/orders con:
 *   account            string  (forzado desde el JWT, ignora lo que mande el cliente)
 *   paymentProviderId  guid
 *   userEmail          string
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

$promotionId = trim((string) ($body['promotionId'] ?? ''));
if ($promotionId === '') {
    http_response_code(400);
    echo json_encode(['Message' => 'Falta el ID de la promoción.', 'Details' => []]);
    exit;
}
unset($body['promotionId']);

// Forzar account desde el token — el cliente no puede modificarlo
$body['account'] = $auth['usr'];

$paymentsUrl = rtrim($_ENV['PAYMENTS_API_URL'] ?? '', '/');
if (!$paymentsUrl) {
    http_response_code(503);
    echo json_encode([
        'Message' => 'El sistema de pagos no está configurado. Contactá a los administradores.',
        'Details' => [],
    ]);
    exit;
}

// JWT estándar (HS256) para autenticar el request al VPS de pagos.
// Clave compartida simétrica: PAYMENT_JWT_SECRET en ambos .env (PHP y .NET).
$paymentJwt = TokenService::generatePaymentJWT((int) $auth['uid'], $auth['usr']);

$ch = curl_init("{$paymentsUrl}/api/promotions/" . rawurlencode($promotionId) . "/orders");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($body),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $paymentJwt,
        'ngrok-skip-browser-warning: true',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
