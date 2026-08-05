<?php
/**
 * TokenService — Tokens firmados con HMAC-SHA256.
 *
 * Formato: base64url(payload_json).base64url(firma)
 * El payload contiene: uid, usr, exp, iat.
 * La firma garantiza que el token no fue modificado.
 *
 * MIGRACIÓN: APP_SECRET debe ser una cadena aleatoria de al menos 32 caracteres.
 * Generarla en el VPS con: php -r "echo bin2hex(random_bytes(32));"
 * y ponerla en .env como APP_SECRET=<valor>.
 * Nunca compartirla ni commitearla.
 */
class TokenService {

    private const ALGO = 'sha256';
    private const TTL  = 24 * 3600; // 24 horas

    /**
     * Genera un token firmado para el usuario autenticado.
     */
    public static function generate(int $userId, string $username): string {
        $payload = json_encode([
            'uid' => $userId,
            'usr' => $username,
            'iat' => time(),
            'exp' => time() + self::TTL,
        ], JSON_THROW_ON_ERROR);

        $b64 = self::b64e($payload);
        $sig = self::b64e(hash_hmac(self::ALGO, $b64, self::secret(), true));

        return $b64 . '.' . $sig;
    }

    /**
     * Verifica el token y devuelve el payload, o null si es inválido o expirado.
     */
    public static function verify(string $token): ?array {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return null;

        [$b64, $sig] = $parts;

        $expectedSig = self::b64e(hash_hmac(self::ALGO, $b64, self::secret(), true));
        if (!hash_equals($expectedSig, $sig)) return null;

        $data = json_decode(self::b64d($b64), true);
        if (!is_array($data)) return null;
        if (($data['exp'] ?? 0) < time()) return null;

        return $data;
    }

    /**
     * Genera un JWT estándar RFC 7519 (HS256) para autenticar requests al VPS de pagos.
     *
     * Clave compartida simétrica: PAYMENT_JWT_SECRET se configura en el .env de cada
     * servidor (PHP y .NET). Nunca viaja por la red — PHP firma, .NET verifica.
     * TTL corto (15 min) porque el JWT es de un solo uso para crear la orden.
     * iat/exp se obtienen de NTP para evitar desfases del reloj del servidor.
     */
    public static function generatePaymentJWT(int $uid, string $username): string {
        $now = self::utcNow();

        $header  = self::b64e(json_encode(
            ['typ' => 'JWT', 'alg' => 'HS256'],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        ));
        $payload = self::b64e(json_encode([
            'iss'  => $_ENV['PAYMENT_JWT_ISS'] ?? 'mupga-api',
            'aud'  => $_ENV['PAYMENT_JWT_AUD'] ?? 'mupga-user',
            'uid'  => $uid,
            'usr'  => $username,
            'role' => 'Player',
            'iat'  => $now,
            'exp'  => $now + 900, // 15 minutos
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        $sig = self::b64e(hash_hmac(self::ALGO, $header . '.' . $payload, self::paymentSecret(), true));

        return $header . '.' . $payload . '.' . $sig;
    }

    /**
     * Devuelve el Unix timestamp UTC real, con varias fuentes independientes del
     * reloj local del VPS (que ya demostró ser inestable — ver incidentes de
     * timezone en CLAUDE.md). Orden: NTP → header Date de la propia API de pagos
     * (HTTPS, ya sabemos que sale porque es el mismo destino del POST de la orden;
     * además sincroniza directo contra el sistema que valida el JWT) → time().
     */
    private static function utcNow(): int {
        return self::ntpTimestamp() ?? self::paymentsApiDateTimestamp() ?? time();
    }

    /**
     * Consulta el header HTTP `Date` de la propia API de pagos (HEAD request) para
     * obtener su hora real. Más confiable que NTP en VPS con salida UDP restringida.
     */
    private static function paymentsApiDateTimestamp(): ?int {
        $paymentsUrl = rtrim($_ENV['PAYMENTS_API_URL'] ?? '', '/');
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

    /**
     * Consulta pool.ntp.org y devuelve el Unix timestamp UTC.
     * Protocolo NTP v3 — paquete de 48 bytes, respuesta en bytes 40-43.
     * Devuelve null si la consulta falla en cualquier punto.
     */
    private static function ntpTimestamp(): ?int {
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

        // Transmit Timestamp: bytes 40-43, segundos desde época NTP (1900-01-01).
        $data  = unpack('Nsec', substr($response, 40, 4));
        $unix  = $data['sec'] - 2208988800; // diferencia entre época NTP y Unix (70 años)

        return $unix > 0 ? $unix : null;
    }

    private static function b64e(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64d(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function secret(): string {
        $s = $_ENV['APP_SECRET'] ?? '';
        if (strlen($s) < 16) {
            throw new RuntimeException('APP_SECRET no está definido o es muy corto. Ver .env.example.');
        }
        return $s;
    }

    private static function paymentSecret(): string {
        $s = $_ENV['PAYMENT_JWT_SECRET'] ?? '';
        if (strlen($s) < 16) {
            throw new RuntimeException('PAYMENT_JWT_SECRET no está configurado. Ver .env.example.');
        }
        return $s;
    }
}
