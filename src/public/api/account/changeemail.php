<?php
/**
 * POST /api/account/changeemail.php  [requiere token]
 * Cambia el email de la cuenta.
 *
 * Body JSON: { "email": "nuevo@email.com" }
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth  = requireAuth();
$body  = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50) {
    http_response_code(400);
    echo json_encode(['error' => 'El email no es válido.', 'field' => 'email']);
    exit;
}

try {
    $db   = Database::get();
    $repo = new AccountRepository($db);

    if ($repo->emailExists($email)) {
        http_response_code(409);
        echo json_encode(['error' => 'Ese email ya está registrado en otra cuenta.', 'field' => 'email']);
        exit;
    }

    $repo->changeEmail($auth['uid'], $email);

    echo json_encode(['message' => 'Email actualizado correctamente.'], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cambiar el email.']);
}
