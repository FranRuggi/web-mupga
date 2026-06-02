<?php
/**
 * POST /api/auth/register.php
 * Crea una cuenta nueva en MEMB_INFO y CashShopData.
 *
 * Body JSON: { "username": "...", "password": "...", "password_confirm": "...", "email": "..." }
 * Respuesta OK:    { "message": "Cuenta creada con éxito." }
 * Respuesta error: { "error": "...", "field": "campo_con_error" (opcional) }
 *
 * SEGURIDAD:
 * - La contraseña nunca se almacena en texto plano — va directo a fn_md5 en SQL Server.
 * - La cuenta creada acá es compatible con el cliente del juego.
 * - Max 10 chars en username y password para compatibilidad con el cliente.
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

$body    = json_decode(file_get_contents('php://input'), true);
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';
$confirm  = $body['password_confirm'] ?? '';
$email    = trim($body['email'] ?? '');

// ── Validaciones ──────────────────────────────────────────────

function fail(string $message, string $field = '', int $code = 400): void {
    http_response_code($code);
    $r = ['error' => $message];
    if ($field) $r['field'] = $field;
    echo json_encode($r, JSON_THROW_ON_ERROR);
    exit;
}

// Username: 4-10 chars, solo letras y números (compatibilidad con cliente MU)
if (!preg_match('/^[a-zA-Z0-9]{4,10}$/', $username)) {
    fail('El usuario debe tener entre 4 y 10 caracteres (solo letras y números).', 'username');
}

// Password: 8-10 chars (el cliente del juego limita a 10)
if (strlen($password) < 8 || strlen($password) > 10) {
    fail('La contraseña debe tener entre 8 y 10 caracteres.', 'password');
}

if ($password !== $confirm) {
    fail('Las contraseñas no coinciden.', 'password_confirm');
}

// Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50) {
    fail('El email no es válido.', 'email');
}

try {
    $db   = Database::get();
    $repo = new AccountRepository($db);

    if ($repo->usernameExists($username)) {
        fail('Ese nombre de usuario ya está en uso.', 'username');
    }

    if ($repo->emailExists($email)) {
        fail('Ese email ya está registrado.', 'email');
    }

    // Crear la cuenta (usa fn_md5 internamente para el password)
    $repo->create($username, $password, $email);

    echo json_encode(['message' => '¡Cuenta creada con éxito! Ya podés iniciar sesión.'], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $msg = 'Error al crear la cuenta. Intentá nuevamente.';
    $payload = ['error' => $msg];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        $payload['debug'] = $e->getMessage();
    }
    echo json_encode($payload);
}
