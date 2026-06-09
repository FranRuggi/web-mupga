<?php
/**
 * POST /api/account/resetstats.php  [requiere token]
 * Resetea las estadísticas del personaje a los valores base de su clase.
 * Devuelve todos los puntos distribuidos al pool de LevelUpPoint.
 *
 * Body JSON: { "character": "NombrePersonaje" }
 *
 * SEGURIDAD: se verifica que el personaje pertenezca a la cuenta del token.
 * SAFE: escritura en Character (Strength/Dex/Vit/Ene/Leadership/LevelUpPoint).
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
    http_response_code(400); echo json_encode(['error' => 'Nombre de personaje requerido.']); exit;
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

    $char = $repo->getByName($charName);
    if (!$char) {
        http_response_code(404); echo json_encode(['error' => 'Personaje no encontrado.']); exit;
    }

    [$bStr, $bAgi, $bVit, $bEne, $bCmd] = $repo->getBaseStats((int) $char['Class']);

    $currentPts = (int)($char['LevelUpPoint'] ?? 0);

    $allocatedAboveBase =
        ((int)($char['Strength']   ?? 0) - $bStr) +
        ((int)($char['Dexterity']  ?? 0) - $bAgi) +
        ((int)($char['Vitality']   ?? 0) - $bVit) +
        ((int)($char['Energy']     ?? 0) - $bEne) +
        ((int)($char['Leadership'] ?? 0) - $bCmd);

    $newPoints = max(0, $currentPts + $allocatedAboveBase);

    $repo->resetStats($charName, [
        'str' => $bStr, 'agi' => $bAgi, 'vit' => $bVit,
        'ene' => $bEne, 'cmd' => $bCmd,
    ], $newPoints);

    echo json_encode([
        'message'    => "Stats de {$charName} reseteados. Puntos disponibles: {$newPoints}.",
        'new_points' => $newPoints,
        'base_stats' => ['str' => $bStr, 'agi' => $bAgi, 'vit' => $bVit, 'ene' => $bEne, 'cmd' => $bCmd],
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al resetear stats.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
