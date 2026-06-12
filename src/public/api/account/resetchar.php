<?php
/**
 * POST /api/account/resetchar.php  [requiere token]
 * Resetea el personaje al nivel 1 e incrementa ResetCount.
 *
 * Requisitos:
 *   - Cuenta offline (ConnectStat = 0 en MEMB_STAT)
 *   - cLevel >= RESET_LEVEL_REQUIRED (default 400)
 *
 * Configuración en .env:
 *   RESET_LEVEL_REQUIRED  — nivel mínimo para resetear (default: 400)
 *   RESET_COST_ZEN        — costo en Zen (default: 0)
 *   RESET_STATS           — resetear stats a la base (default: true)
 *   RESET_BONUS_POINTS    — LevelUpPoints extra dados tras el reset (default: 0)
 *
 * Body JSON: { "character": "NombrePersonaje" }
 *
 * SAFE: escritura en Character (cLevel, ResetCount, Strength, Dexterity, Vitality,
 *       Energy, Leadership, LevelUpPoint, Money). Ver capability-matrix.md.
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

$levelRequired = (int) ($_ENV['RESET_LEVEL_REQUIRED'] ?? 400);
$costZen       = (int) ($_ENV['RESET_COST_ZEN']       ?? 0);
$resetStats    = ($_ENV['RESET_STATS']                 ?? 'true') !== 'false';
$bonusPoints   = (int) ($_ENV['RESET_BONUS_POINTS']   ?? 0);
$maxResets     = (int) ($_ENV['RESET_MAX_RESETS']      ?? 0);

try {
    $db       = Database::get();
    $charRepo = new CharacterRepository($db);
    $accRepo  = new AccountRepository($db);

    if ($accRepo->isOnline($auth['usr'])) {
        http_response_code(409);
        echo json_encode(['error' => 'Cuenta en línea. Desconectate del servidor para continuar.']);
        exit;
    }

    if (!$charRepo->belongsToAccount($charName, $auth['usr'])) {
        http_response_code(403);
        echo json_encode(['error' => 'El personaje no pertenece a tu cuenta.']);
        exit;
    }

    $char = $charRepo->getByName($charName);
    if (!$char) {
        http_response_code(404); echo json_encode(['error' => 'Personaje no encontrado.']); exit;
    }

    $currentLevel  = (int)($char['cLevel']     ?? 0);
    $currentResets = (int)($char['ResetCount'] ?? 0);
    $currentZen    = (int)($char['Money']       ?? 0);

    if ($currentLevel < $levelRequired) {
        http_response_code(400);
        echo json_encode([
            'error' => "Necesitás nivel {$levelRequired} para resetear. Nivel actual: {$currentLevel}.",
        ]);
        exit;
    }

    if ($maxResets > 0 && $currentResets >= $maxResets) {
        http_response_code(400);
        echo json_encode([
            'error' => "Alcanzaste el máximo de resets permitidos ({$maxResets}).",
        ]);
        exit;
    }

    if ($costZen > 0 && $currentZen < $costZen) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Zen insuficiente. Necesitás ' . number_format($costZen, 0, ',', '.') . ' Zen para resetear.',
        ]);
        exit;
    }

    $newResets = $currentResets + 1;
    $resetData = [
        'level'    => 1,
        'resets'   => $newResets,
        'zen_cost' => $costZen,
    ];

    if ($resetStats) {
        [$bStr, $bAgi, $bVit, $bEne, $bCmd] = $charRepo->getBaseStats((int) $char['Class']);
        $resetData['str']    = $bStr;
        $resetData['agi']    = $bAgi;
        $resetData['vit']    = $bVit;
        $resetData['ene']    = $bEne;
        $resetData['cmd']    = $bCmd;
        $resetData['points'] = $bonusPoints * $newResets;
    }

    $charRepo->reset($charName, $resetData);

    echo json_encode([
        'message'    => "¡{$charName} reseteado exitosamente! Resets: {$newResets}.",
        'new_resets' => $newResets,
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al resetear el personaje.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
