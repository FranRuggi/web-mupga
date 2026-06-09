<?php
/**
 * POST /api/account/unstick.php  [requiere token]
 * Mueve el personaje indicado a Lorencia (mapa 0, coord 125,125).
 *
 * Body JSON: { "character": "NombrePersonaje" }
 *
 * SEGURIDAD: Se verifica que el personaje pertenezca a la cuenta del token.
 * SAFE: escritura en Character.MapNumber/MapPosX/MapPosY — segura con jugador online.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth      = requireAuth();
$body      = json_decode(file_get_contents('php://input'), true);
$charName  = trim($body['character'] ?? '');

if (!$charName) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre de personaje requerido.']);
    exit;
}

try {
    $db   = Database::get();
    $repo = new CharacterRepository($db);

    if ((new AccountRepository($db))->isOnline($auth['usr'])) {
        http_response_code(409);
        echo json_encode(['error' => 'Cuenta en línea. Desconectate del servidor para continuar.']);
        exit;
    }

    if (!$repo->belongsToAccount($charName, $auth['usr'])) {
        http_response_code(403);
        echo json_encode(['error' => 'El personaje no pertenece a tu cuenta.']);
        exit;
    }

    $repo->unstick($charName);

    echo json_encode(['message' => "¡{$charName} fue enviado a Lorencia correctamente."]);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al hacer unstick.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        $payload['debug'] = $e->getMessage();
    }
    echo json_encode($payload);
}
