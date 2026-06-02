<?php
/**
 * POST /api/account/addstats.php  [requiere token]
 * Agrega puntos de estadísticas al personaje desde el pool de LevelUpPoint.
 *
 * Body JSON: {
 *   "character": "NombrePersonaje",
 *   "str": 0, "agi": 0, "vit": 0, "ene": 0, "cmd": 0
 * }
 * Todos los campos de stats son opcionales; se ignoran si valen 0.
 *
 * SEGURIDAD: se verifica que el personaje pertenezca a la cuenta del token y que
 * no se gasten más puntos de los disponibles en LevelUpPoint.
 * SAFE: escritura en Character (stats + LevelUpPoint) — segura con jugador online.
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
$addStr   = max(0, (int)($body['str'] ?? 0));
$addAgi   = max(0, (int)($body['agi'] ?? 0));
$addVit   = max(0, (int)($body['vit'] ?? 0));
$addEne   = max(0, (int)($body['ene'] ?? 0));
$addCmd   = max(0, (int)($body['cmd'] ?? 0));

$totalToAdd = $addStr + $addAgi + $addVit + $addEne + $addCmd;

if (!$charName) {
    http_response_code(400); echo json_encode(['error' => 'Nombre de personaje requerido.']); exit;
}
if ($totalToAdd === 0) {
    http_response_code(400); echo json_encode(['error' => 'Debés agregar al menos 1 punto.']); exit;
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
    if (!$char) {
        http_response_code(404); echo json_encode(['error' => 'Personaje no encontrado.']); exit;
    }

    $available = (int)($char['LevelUpPoint'] ?? 0);
    if ($totalToAdd > $available) {
        http_response_code(400);
        echo json_encode([
            'error'     => "No tenés suficientes puntos. Disponibles: {$available}.",
            'available' => $available,
        ]);
        exit;
    }

    $newStats = [
        'str' => (int)($char['Strength']   ?? 0) + $addStr,
        'agi' => (int)($char['Dexterity']  ?? 0) + $addAgi,
        'vit' => (int)($char['Vitality']   ?? 0) + $addVit,
        'ene' => (int)($char['Energy']     ?? 0) + $addEne,
        'cmd' => (int)($char['Leadership'] ?? 0) + $addCmd,
    ];

    $repo->addStats($charName, $newStats, $totalToAdd);

    echo json_encode([
        'message'   => "Stats actualizados. Puntos restantes: " . ($available - $totalToAdd) . '.',
        'remaining' => $available - $totalToAdd,
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al agregar stats.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
