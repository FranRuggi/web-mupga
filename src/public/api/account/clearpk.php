<?php
/**
 * POST /api/account/clearpk.php  [requiere token]
 * Limpia el estado PK del personaje indicado.
 *
 * Body JSON: { "character": "NombrePersonaje" }
 *
 * SEGURIDAD: Se verifica que el personaje pertenezca a la cuenta del token.
 * SAFE: escritura en Character.PkLevel/PkTime — segura con jugador online.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth     = requireAuth();
$body     = json_decode(file_get_contents('php://input'), true);
$charName = trim($body['character'] ?? '');

if (!$charName) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre de personaje requerido.']);
    exit;
}

try {
    $db   = Database::get();
    $repo = new CharacterRepository($db);

    if (!$repo->belongsToAccount($charName, $auth['usr'])) {
        http_response_code(403);
        echo json_encode(['error' => 'El personaje no pertenece a tu cuenta.']);
        exit;
    }

    $char = $repo->getByName($charName);

    // Solo limpiar si el personaje tiene PK activo (PkLevel != 3 = Commoner)
    if (isset($char['PkLevel']) && (int) $char['PkLevel'] === 3) {
        http_response_code(400);
        echo json_encode(['error' => 'El personaje no tiene estado PK activo.']);
        exit;
    }

    $repo->clearPK($charName);

    echo json_encode(['message' => "Estado PK de {$charName} limpiado correctamente."]);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al limpiar el estado PK.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        $payload['debug'] = $e->getMessage();
    }
    echo json_encode($payload);
}
