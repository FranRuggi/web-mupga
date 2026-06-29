<?php
require __DIR__ . '/src/bootstrap.php';

// ── JWT de pagos ──────────────────────────────────────────────
$token = TokenService::generatePaymentJWT(308, 'ElAmante');
echo "=== JWT de pagos ===" . PHP_EOL;
echo $token . PHP_EOL . PHP_EOL;

[$h, $p] = explode('.', $token);
$payload = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
echo "iat: {$payload['iat']}  → " . gmdate('Y-m-d H:i:s', $payload['iat']) . " UTC" . PHP_EOL;
echo "exp: {$payload['exp']}  → " . gmdate('Y-m-d H:i:s', $payload['exp']) . " UTC" . PHP_EOL;

// ── Proxy currencies ──────────────────────────────────────────
echo PHP_EOL . "=== Proxy /api/currencies ===" . PHP_EOL;
$paymentsUrl = rtrim($_ENV['PAYMENTS_API_URL'] ?? '', '/');
if (!$paymentsUrl) {
    echo "PAYMENTS_API_URL no configurado en .env" . PHP_EOL;
    exit;
}
echo "URL: {$paymentsUrl}/api/currencies" . PHP_EOL;

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
    echo "ERROR: no se pudo conectar" . PHP_EOL;
    exit;
}

echo "HTTP {$httpCode}" . PHP_EOL;
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
