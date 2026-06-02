<?php
/**
 * GET /api/rankings.php?type=resets&limit=25
 * Devuelve un ranking de personajes en formato JSON.
 *
 * Parámetros:
 *   type  — resets | level | kills | masterresets | master | guilds
 *   limit — int 1–100, default 25
 *
 * Respuesta: [{"name":"...","class":16,"level":400,"resets":150,"map":0}, ...]
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

// ── Validación de parámetros ──
$allowed = ['resets', 'level', 'kills', 'masterresets', 'master', 'guilds'];
$type    = $_GET['type'] ?? 'resets';
$limit   = min(max((int) ($_GET['limit'] ?? 25), 1), 100);

if (!in_array($type, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de ranking inválido. Válidos: ' . implode(', ', $allowed)]);
    exit;
}

try {
    $db   = Database::get();
    $repo = new RankingsRepository($db);

    $rows = match ($type) {
        'resets'      => $repo->getByResets($limit),
        'level'       => $repo->getByLevel($limit),
        'kills'       => $repo->getByKills($limit),
        'masterresets'=> $repo->getByMasterResets($limit),
        'master'      => $repo->getByMasterLevel($limit),
        'guilds'      => $repo->getGuildsByScore($limit),
        default       => [],
    };

    // Normalizar claves a camelCase para el frontend
    if ($type === 'guilds') {
        $result = array_map(fn($r) => [
            'name'   => $r['G_Name']   ?? '',
            'master' => $r['G_Master'] ?? '',
            'score'  => (int) ($r['G_Score'] ?? 0),
            'count'  => (int) ($r['G_Count'] ?? 0),
            'mark'   => $r['G_Mark_Hex'] ?? null,
        ], $rows);
    } else {
        $result = array_map(fn($r) => [
            'name'          => $r['Name']          ?? '',
            'class'         => (int) ($r['Class']         ?? 0),
            'level'         => (int) ($r['cLevel']        ?? 0),
            'resets'        => (int) ($r['ResetCount']    ?? 0),
            'masterResets'  => (int) ($r['MasterResetCount'] ?? 0),
            'masterLevel'   => (int) ($r['mLevel']        ?? 0),
            'pkCount'       => (int) ($r['PkCount']       ?? 0),
            'pkLevel'       => (int) ($r['PkLevel']       ?? 0),
            'map'           => (int) ($r['MapNumber']     ?? 0),
        ], $rows);
    }

    echo json_encode($result, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno'], JSON_THROW_ON_ERROR);
}
