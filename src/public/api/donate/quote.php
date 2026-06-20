<?php
/**
 * GET /api/donate/quote.php?basecurrency=WC&amount=1000&quotecurrency=ARS
 *
 * Proxy hacia PAYMENTS_API_URL/api/currencies/quote.
 * Valida y sanea los parámetros antes de reenviar.
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

$from   = trim($_GET['basecurrency']  ?? '');
$to     = trim($_GET['quotecurrency'] ?? '');
$amount = (int) ($_GET['amount']       ?? 0);

if (!$from || !$to || $amount <= 0 || $amount > 100000) {
    http_response_code(400);
    echo json_encode(['Message' => 'Parámetros inválidos.']);
    exit;
}

$url = $paymentsUrl . '/api/currencies/quote?' . http_build_query([
    'basecurrency'  => $from,
    'amount'        => $amount,
    'quotecurrency' => $to,
]);

$ch = curl_init($url);
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
