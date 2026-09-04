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

    // Mismo criterio que el registro: el email no es único, pero tiene techo.
    // Si no, alguien con 3 cuentas (lo normal en MU) no podría ponerles el
    // mismo email a todas.
    $tope = AccountRepository::MAX_CUENTAS_POR_EMAIL;
    if ($repo->countByEmail($email) >= $tope) {
        http_response_code(409);
        echo json_encode([
            'error' => "Ese email ya tiene {$tope} cuentas, que es el máximo.",
            'field' => 'email',
        ]);
        exit;
    }

    $repo->changeEmail($auth['uid'], $email);

    echo json_encode(['message' => 'Email actualizado correctamente.'], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cambiar el email.']);
}
