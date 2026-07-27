<?php
/**
 * Diagnóstico temporal — genera un JWT real con TokenService::generatePaymentJWT()
 * y lo prueba contra un endpoint de SOLO LECTURA de la API de pagos
 * (GET /api/currencies/quote, no crea ninguna orden), para ver la respuesta
 * COMPLETA incluidos los headers — cosa que hoy se pierde, porque order.php solo
 * reenvía status + body de la API externa, nunca sus headers (ahí suele viajar
 * el detalle real de un 401 de JWT, ej. WWW-Authenticate: Bearer error="...").
 *
 * Uso (en el VPS): php tools/debug_payment_jwt.php [uid] [usuario]
 * Ej:              php tools/debug_payment_jwt.php 118 ruggi
 *
 * Borrar este archivo una vez resuelto el diagnóstico.
 */

require_once __DIR__ . '/../src/config/env.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../src/lib/TokenService.php';

$uid      = isset($argv[1]) ? (int) $argv[1] : 118;
$username = $argv[2] ?? 'ruggi';

function b64d(string $s): string {
    $s = strtr($s, '-_', '+/');
    $s .= str_repeat('=', (4 - strlen($s) % 4) % 4);
    return base64_decode($s);
}

$jwt = TokenService::generatePaymentJWT($uid, $username);
[$header, $payload, $sig] = explode('.', $jwt);

echo "=== JWT generado (uid={$uid}, usr={$username}) ===\n";
echo "Header:  " . b64d($header) . "\n";
echo "Payload: " . b64d($payload) . "\n";
echo "Firma (base64url, " . strlen($sig) . " chars): {$sig}\n";
echo "Token completo:\n{$jwt}\n\n";

$paymentsUrl = rtrim($_ENV['PAYMENTS_API_URL'] ?? '', '/');
if (!$paymentsUrl) {
    echo "PAYMENTS_API_URL no está seteada en el .env — no se puede probar contra la API.\n";
    exit(1);
}

$url = $paymentsUrl . '/api/currencies/quote?baseCurrency=WC&quoteCurrency=ARS&amount=1000';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Authorization: Bearer ' . $jwt,
    ],
    CURLOPT_HEADER         => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$curlErr    = curl_error($ch);
curl_close($ch);

echo "=== Prueba GET {$url} ===\n";
if ($response === false) {
    echo "curl falló: {$curlErr}\n";
    exit(1);
}

$respHeaders = substr($response, 0, $headerSize);
$respBody    = substr($response, $headerSize);

echo "HTTP {$httpCode}\n\n";
echo "--- Headers de la respuesta ---\n{$respHeaders}\n";
echo "--- Body de la respuesta ---\n{$respBody}\n";

echo "\n=== Lectura ===\n";
if ($httpCode === 200) {
    echo "200 OK: el JWT generado por PHP es válido para la API de pagos. El 401 que\n";
    echo "ven en /donate/ no es un problema del JWT en sí — hay que mirar qué es distinto\n";
    echo "en el POST /api/orders puntualmente (revisar el body que arma el proxy, el rol\n";
    echo "requerido para ESE endpoint específico, o si valida algo más sobre la cuenta).\n";
} else {
    echo "Sigue fallando igual acá (endpoint de solo lectura, sin nada de order.php de por\n";
    echo "medio) => el problema es el JWT/secreto en sí, no algo puntual de /api/orders.\n";
    echo "Revisar WWW-Authenticate en los headers de arriba (si la API lo manda) y comparar\n";
    echo "PAYMENT_JWT_SECRET / PAYMENT_JWT_ISS / PAYMENT_JWT_AUD del .env contra el\n";
    echo "appsettings del lado .NET, caracter por caracter (comillas, espacios, saltos de línea).\n";
}
