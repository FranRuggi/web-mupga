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
    private const TTL  = 7 * 24 * 3600; // 7 días

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
}
