<?php
/**
 * Diagnóstico temporal — reproduce la cadena de fuentes que usa
 * TokenService::utcNow() (NTP → Date de la API de pagos → time()) y muestra qué
 * valor gana, para descartar clock skew del VPS como causa de los 401 en la API
 * de pagos.
 *
 * Uso (en el VPS, con el .env cargado en el entorno o ajustando $paymentsApiUrl
 * abajo): php tools/debug_jwt_clock.php
 * Borrar este archivo una vez resuelto el diagnóstico — no depende de bootstrap.php
 * ni de ningún otro archivo del sitio.
 */

$paymentsApiUrl = getenv('PAYMENTS_API_URL') ?: '';

function ntpTimestamp(): ?int {
    $sock = @fsockopen('udp://pool.ntp.org', 123, $errno, $errstr, 2);
    if ($sock === false) return null;

    $response = '';
    try {
        fwrite($sock, "\x1b" . str_repeat("\0", 47));
        stream_set_timeout($sock, 2);
        $response = fread($sock, 48);
    } finally {
        fclose($sock);
    }

    if (strlen($response) < 48) return null;

    $data = unpack('Nsec', substr($response, 40, 4));
    $unix = $data['sec'] - 2208988800;

    return $unix > 0 ? $unix : null;
}

function paymentsApiDateTimestamp(string $paymentsApiUrl): ?int {
    $paymentsUrl = rtrim($paymentsApiUrl, '/');
    if (!$paymentsUrl) return null;

    $ch = curl_init($paymentsUrl);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY         => true,
        CURLOPT_HEADER         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $headers = curl_exec($ch);
    curl_close($ch);

    if ($headers === false || !preg_match('/^Date:\s*(.+)$/mi', $headers, $m)) {
        return null;
    }

    $ts = strtotime(trim($m[1]));
    return $ts !== false ? $ts : null;
}

echo "=== Diagnóstico de clock para el JWT de pagos ===\n\n";

$localTime = time();
echo "1) time() del servidor:\n";
echo "   Unix: {$localTime}\n";
echo "   UTC:  " . gmdate('Y-m-d H:i:s', $localTime) . "\n\n";

$ntpTime = ntpTimestamp();
echo "2) NTP (pool.ntp.org, UDP 123):\n";
if ($ntpTime === null) {
    echo "   FALLÓ — bloqueado o inalcanzable desde este VPS.\n\n";
} else {
    echo "   Unix: {$ntpTime}\n";
    echo "   UTC:  " . gmdate('Y-m-d H:i:s', $ntpTime) . "\n";
    echo "   Diferencia vs time(): " . ($ntpTime - $localTime) . " segundos\n\n";
}

echo "3) Header Date de la API de pagos (" . ($paymentsApiUrl ?: '[PAYMENTS_API_URL no está seteada en el entorno]') . "):\n";
if (!$paymentsApiUrl) {
    echo "   SIN PROBAR — seteá PAYMENTS_API_URL en el entorno o hardcodealo arriba para probar.\n\n";
    $apiTime = null;
} else {
    $apiTime = paymentsApiDateTimestamp($paymentsApiUrl);
    if ($apiTime === null) {
        echo "   FALLÓ — no se pudo conectar o no devolvió header Date.\n\n";
    } else {
        echo "   Unix: {$apiTime}\n";
        echo "   UTC:  " . gmdate('Y-m-d H:i:s', $apiTime) . "\n";
        echo "   Diferencia vs time(): " . ($apiTime - $localTime) . " segundos\n\n";
    }
}

$winner = $ntpTime ?? $apiTime ?? $localTime;
$winnerName = $ntpTime !== null ? 'NTP' : ($apiTime !== null ? 'Date de la API de pagos' : 'time() (fallback final)');

echo "=== Resultado ===\n";
echo "generatePaymentJWT() usaría hoy: {$winnerName} → " . gmdate('Y-m-d H:i:s', $winner) . " UTC\n";
echo "Comparar contra la hora real (https://time.is/UTC). Un desfase de más de\n";
echo "un par de minutos explica los 401 (exp/iat calculados con hora incorrecta).\n";
