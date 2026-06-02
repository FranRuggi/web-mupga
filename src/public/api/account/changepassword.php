<?php
/**
 * POST /api/account/changepassword.php  [requiere token]
 * Cambia la contraseña de la cuenta.
 *
 * Body JSON: { "current_password": "...", "new_password": "...", "confirm_password": "..." }
 *
 * SEGURIDAD: Las contraseñas nunca se almacenan — van a fn_md5 en SQL Server.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$current  = $body['current_password']  ?? '';
$new      = $body['new_password']       ?? '';
$confirm  = $body['confirm_password']   ?? '';

if (!$current || !$new || !$confirm) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son requeridos.']);
    exit;
}

if (strlen($new) < 8 || strlen($new) > 10) {
    http_response_code(400);
    echo json_encode(['error' => 'La nueva contraseña debe tener entre 8 y 10 caracteres.', 'field' => 'new_password']);
    exit;
}

if ($new !== $confirm) {
    http_response_code(400);
    echo json_encode(['error' => 'Las contraseñas nuevas no coinciden.', 'field' => 'confirm_password']);
    exit;
}

try {
    $db   = Database::get();
    $repo = new AccountRepository($db);

    if (!$repo->validateCredentials($auth['usr'], $current)) {
        http_response_code(401);
        echo json_encode(['error' => 'La contraseña actual es incorrecta.', 'field' => 'current_password']);
        exit;
    }

    $repo->changePassword($auth['uid'], $auth['usr'], $new);

    echo json_encode(['message' => 'Contraseña actualizada correctamente.'], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cambiar la contraseña.']);
}
