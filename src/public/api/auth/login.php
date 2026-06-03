<?php
/**
 * POST /api/auth/login.php
 * Autentica al usuario y devuelve un token firmado.
 *
 * Body JSON: { "username": "...", "password": "..." }
 * Respuesta OK:    { "token": "...", "username": "...", "user_id": N }
 * Respuesta error: { "error": "..." }
 *
 * SEGURIDAD:
 * - La contraseña viaja por HTTPS y se valida en SQL Server via fn_md5.
 * - Nunca se logea ni se almacena en PHP.
 * - Intentos fallidos se registran en WEBENGINE_FLA (anti-brute force).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

// ── Validación básica ──
if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña son requeridos.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9]{4,10}$/', $username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario inválido.']);
    exit;
}

try {
    $db      = Database::get();
    $repo    = new AccountRepository($db);
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $maxAttempts = 5;
    $lockMinutes = 15;
    $fla         = null;

    // ── Anti-brute force: verificar intentos fallidos ──
    // La tabla WEBENGINE_FLA puede no existir en entornos de solo juego → try/catch
    try {
        $flaCheck = $db->prepare(
            'SELECT failed_attempts, unlock_timestamp FROM WEBENGINE_FLA WHERE ip_address = ?'
        );
        $flaCheck->execute([$ip]);
        $fla = $flaCheck->fetch() ?: null;
    } catch (Throwable $e) {
        $fla = null; // tabla no disponible → saltar anti-brute force
    }

    if ($fla && $fla['failed_attempts'] >= $maxAttempts) {
        if (time() < (int) $fla['unlock_timestamp']) {
            http_response_code(429);
            echo json_encode(['error' => "Demasiados intentos fallidos. Intentá en {$lockMinutes} minutos."]);
            exit;
        }
        try { $db->prepare('DELETE FROM WEBENGINE_FLA WHERE ip_address = ?')->execute([$ip]); } catch (Throwable $e) {}
        $fla = null;
    }

    // ── Validar credenciales ──
    if (!$repo->validateCredentials($username, $password)) {
        // Registrar intento fallido (silenciar si la tabla no existe)
        $unlock = time() + $lockMinutes * 60;
        try {
            if ($fla) {
                $db->prepare(
                    'UPDATE WEBENGINE_FLA SET failed_attempts = failed_attempts + 1, unlock_timestamp = ?, timestamp = ? WHERE ip_address = ?'
                )->execute([$unlock, time(), $ip]);
            } else {
                $db->prepare(
                    'INSERT INTO WEBENGINE_FLA (username, ip_address, failed_attempts, unlock_timestamp, timestamp) VALUES (?,?,1,?,?)'
                )->execute([$username, $ip, $unlock, time()]);
            }
        } catch (Throwable $e) {}

        http_response_code(401);
        echo json_encode(['error' => 'Usuario o contraseña incorrectos.']);
        exit;
    }

    // ── Login exitoso ──
    try { $db->prepare('DELETE FROM WEBENGINE_FLA WHERE ip_address = ?')->execute([$ip]); } catch (Throwable $e) {}

    $account = $repo->getByUsername($username);
    if (!$account) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno.']);
        exit;
    }

    // Verificar cuenta no bloqueada
    if (trim($account['bloc_code']) !== '0') {
        http_response_code(403);
        echo json_encode(['error' => 'Tu cuenta está suspendida. Contactá al soporte.']);
        exit;
    }

    $token = TokenService::generate((int) $account['memb_guid'], $account['memb___id']);

    echo json_encode([
        'token'    => $token,
        'username' => $account['memb___id'],
        'user_id'  => (int) $account['memb_guid'],
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno del servidor.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        $payload['debug'] = $e->getMessage();
    }
    echo json_encode($payload);
}
