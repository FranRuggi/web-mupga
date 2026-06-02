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

// ── Stats base por clase (código de clase → [str, agi, vit, ene, cmd]) ──────
// Fuente: valores estándar de MU Online Season 6.
// Los códigos de clase evolucionada heredan los mismos stats base.
const BASE_STATS = [
    0  => [18, 18, 15, 30, 0],   // Dark Wizard / Soul Master / Grand Master
    1  => [18, 18, 15, 30, 0],
    3  => [18, 18, 15, 30, 0],
    7  => [18, 18, 15, 30, 0],
    16 => [28, 20, 25, 10, 0],   // Dark Knight / Blade Knight / Blade Master
    17 => [28, 20, 25, 10, 0],
    19 => [28, 20, 25, 10, 0],
    23 => [28, 20, 25, 10, 0],
    32 => [22, 25, 20, 15, 0],   // Fairy Elf / Muse Elf / High Elf
    33 => [22, 25, 20, 15, 0],
    35 => [22, 25, 20, 15, 0],
    39 => [22, 25, 20, 15, 0],
    48 => [26, 26, 26, 16, 0],   // Magic Gladiator / Duel Master
    50 => [26, 26, 26, 16, 0],
    64 => [26, 20, 20, 15, 30],  // Dark Lord / Lord Emperor
    66 => [26, 20, 20, 15, 30],
    70 => [26, 20, 20, 15, 30],
    80 => [18, 21, 21, 23, 0],   // Summoner / Bloody Summoner / Dimension Master
    81 => [18, 21, 21, 23, 0],
    83 => [18, 21, 21, 23, 0],
    96 => [32, 27, 25, 20, 0],   // Rage Fighter / Fist Master
    98 => [32, 27, 25, 20, 0],
    112=> [21, 18, 18, 20, 0],   // Grow Lancer
    114=> [21, 18, 18, 20, 0],
    128=> [18, 18, 15, 30, 0],   // Rune Mage
    129=> [18, 18, 15, 30, 0],
    144=> [26, 26, 26, 16, 0],   // Slayer
    145=> [26, 26, 26, 16, 0],
    160=> [26, 26, 26, 16, 0],   // Gun Crusher
    161=> [26, 26, 26, 16, 0],
];

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

    $classCode = (int) $char['Class'];
    // Usar la clase base (borrar bits de evolución): los primeros bits identifican la clase.
    $baseClass = $classCode;
    $base      = BASE_STATS[$baseClass] ?? BASE_STATS[0];
    [$bStr, $bAgi, $bVit, $bEne, $bCmd] = $base;

    $currentStr   = (int)($char['Strength']   ?? 0);
    $currentAgi   = (int)($char['Dexterity']  ?? 0);
    $currentVit   = (int)($char['Vitality']   ?? 0);
    $currentEne   = (int)($char['Energy']     ?? 0);
    $currentCmd   = (int)($char['Leadership'] ?? 0);
    $currentPts   = (int)($char['LevelUpPoint'] ?? 0);

    // Puntos que estaban distribuidos sobre la base = los devolvemos al pool
    $allocatedAboveBase = ($currentStr - $bStr) + ($currentAgi - $bAgi)
                        + ($currentVit - $bVit) + ($currentEne - $bEne)
                        + ($currentCmd - $bCmd);
    $newPoints = max(0, $currentPts + $allocatedAboveBase);

    $repo->resetStats($charName, [
        'str' => $bStr, 'agi' => $bAgi, 'vit' => $bVit,
        'ene' => $bEne, 'cmd' => $bCmd,
    ], $newPoints);

    echo json_encode([
        'message'    => "Stats de {$charName} reseteados. Puntos disponibles: {$newPoints}.",
        'new_points' => $newPoints,
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al resetear stats.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
