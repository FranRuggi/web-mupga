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
     */
    public static function generatePaymentJWT(int $uid, string $username): string {
        $now = time();

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
