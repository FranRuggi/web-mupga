<?php
/**
 * GET /api/rankings.php?type=resets&limit=100&account=<accountId>
 * Devuelve el ranking pedido en formato JSON.
 *
 * Parámetros:
 *   type    — resets | level | kills | masterresets | master | guilds
 *   limit   — int 1–100, default 100
 *   account — (opcional) AccountID del jugador logueado; si está presente, la
 *             respuesta incluye la posición del jugador aunque no esté en el top.
 *
 * Respuesta sin `account`: array plano de objetos.
 * Respuesta con `account`:  { "rows": [...], "player": {..., "position": N} | null }
 *
 * Cuentas excluidas (admins): RANKINGS_EXCLUDED_ACCOUNTS en .env (separadas por coma).
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

// ── Parámetros ──────────────────────────────────────────────────────────────
$allowed = ['resets', 'level', 'kills', 'masterresets', 'master', 'guilds'];
$type    = $_GET['type'] ?? 'resets';
$limit   = min(max((int) ($_GET['limit'] ?? 100), 1), 100);
$account = trim($_GET['account'] ?? '');

if (!in_array($type, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de ranking inválido. Válidos: ' . implode(', ', $allowed)]);
    exit;
}

// Cuentas excluidas (admins) — leídas del .env
$excludedRaw     = $_ENV['RANKINGS_EXCLUDED_ACCOUNTS'] ?? '';
$excludeAccounts = array_filter(array_map('trim', explode(',', $excludedRaw)));

// ── Normalización de filas ──────────────────────────────────────────────────
function normalizePlayerRow(array $r): array {
    return [
        'name'         => $r['Name']               ?? '',
        'class'        => (int) ($r['Class']        ?? 0),
        'level'        => (int) ($r['cLevel']       ?? 0),
        'resets'       => (int) ($r['ResetCount']   ?? 0),
        'masterResets' => (int) ($r['MasterResetCount'] ?? 0),
        'masterLevel'  => (int) ($r['mLevel']       ?? 0),
        'pkCount'      => (int) ($r['PkCount']      ?? 0),
        'pkLevel'      => (int) ($r['PkLevel']      ?? 0),
        'map'          => (int) ($r['MapNumber']    ?? 0),
        'country'      => $r['CountryCode']         ?? null,
    ];
}

try {
    $db   = Database::get();
    $repo = new RankingsRepository($db);

    // ── Obtener filas del ranking ───────────────────────────────────────────
    if ($type === 'guilds') {
        $rows = $repo->getGuildsByScore($limit);
        $result = array_map(fn($r) => [
            'name'   => $r['G_Name']   ?? '',
            'master' => $r['G_Master'] ?? '',
            'score'  => (int) ($r['G_Score'] ?? 0),
            'count'  => (int) ($r['G_Count'] ?? 0),
            'mark'   => $r['G_Mark_Hex'] ?? null,
        ], $rows);
    } else {
        switch ($type) {
            case 'resets':       $rows = $repo->getByResets($limit, $excludeAccounts);       break;
            case 'level':        $rows = $repo->getByLevel($limit, $excludeAccounts);        break;
            case 'kills':        $rows = $repo->getByKills($limit, $excludeAccounts);        break;
            case 'masterresets': $rows = $repo->getByMasterResets($limit, $excludeAccounts); break;
            case 'master':       $rows = $repo->getByMasterLevel($limit, $excludeAccounts);  break;
            default:             $rows = [];
        }
        $result = array_map('normalizePlayerRow', $rows);
    }

    // ── Posición del jugador (solo para rankings de personaje) ──────────────
    // getPlayerCharacterRank usa un CTE con columnas que pueden no existir en
    // backups viejos (mLevel, PkCount, PkLevel). Si falla, se silencia y los
    // datos del ranking se muestran igual, sin la posición del jugador.
    $player = null;
    if ($account !== '' && $type !== 'guilds') {
        try {
            $playerRow = $repo->getPlayerCharacterRank($account, $type, $excludeAccounts);
            $player    = $playerRow
                ? array_merge(normalizePlayerRow($playerRow), ['position' => (int) $playerRow['position']])
                : null;

            foreach ($result as &$row) {
                if ($row['name'] === ($player['name'] ?? '')) {
                    $row['isPlayer'] = true;
                }
            }
            unset($row);
        } catch (Throwable) {
            $player = null;
        }

        echo json_encode(['rows' => $result, 'player' => $player], JSON_THROW_ON_ERROR);
    } else {
        echo json_encode($result, JSON_THROW_ON_ERROR);
    }

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        $payload['debug'] = $e->getMessage();
    }
    echo json_encode($payload, JSON_THROW_ON_ERROR);
}
