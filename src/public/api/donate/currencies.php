<?php
/**
 * GET /api/donate/currencies.php
 *
 * Proxy hacia PAYMENTS_API_URL/api/currencies.
 * El browser nunca habla directamente con el VPS de pagos.
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

$paymentsUrl = rtrim($_ENV['PAYMENTS_API_URL'] ?? '', '/');
if (!$paymentsUrl) {
    http_response_code(503);
    echo json_encode(['Message' => 'El sistema de pagos no está configurado.']);
    exit;
}

$ch = curl_init("{$paymentsUrl}/api/currencies");
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$response = curl_exec($ch);
$httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode === 0) {
    http_response_code(503);
    echo json_encode(['Message' => 'No se pudo conectar con el sistema de pagos.']);
    exit;
}

http_response_code($httpCode);
echo $response;
