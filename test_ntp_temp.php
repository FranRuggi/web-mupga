<?php
require __DIR__ . '/src/bootstrap.php';

// Comparar tiempo local vs NTP
$localTime = time();

// Llamar ntpTimestamp() vía reflexión (método privado)
$ref = new ReflectionMethod('TokenService', 'ntpTimestamp');
$ref->setAccessible(true);
$ntpTime = $ref->invoke(null);

echo "Tiempo local (time()):  $localTime  → " . gmdate('Y-m-d H:i:s', $localTime) . " UTC" . PHP_EOL;
if ($ntpTime !== null) {
    $diff = $ntpTime - $localTime;
    echo "Tiempo NTP:             $ntpTime  → " . gmdate('Y-m-d H:i:s', $ntpTime) . " UTC" . PHP_EOL;
    echo "Diferencia:             {$diff}s" . PHP_EOL;
} else {
    echo "NTP: FALLÓ (timeout o firewall) — se usará time() como fallback" . PHP_EOL;
}

echo PHP_EOL;

// Generar JWT con la lógica real
$token = TokenService::generatePaymentJWT(308, 'ElAmante');
echo "JWT generado:" . PHP_EOL . $token . PHP_EOL . PHP_EOL;

[$h, $p] = explode('.', $token);
$payload = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
echo "Payload decodificado:" . PHP_EOL;
echo "  iat: {$payload['iat']}  → " . gmdate('Y-m-d H:i:s', $payload['iat']) . " UTC" . PHP_EOL;
echo "  exp: {$payload['exp']}  → " . gmdate('Y-m-d H:i:s', $payload['exp']) . " UTC" . PHP_EOL;
echo "  usr: {$payload['usr']}" . PHP_EOL;
