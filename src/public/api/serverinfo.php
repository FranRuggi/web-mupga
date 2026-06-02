<?php
/**
 * GET /api/serverinfo.php
 * Devuelve la información estática + dinámica del servidor.
 * Respuesta: {"season":"Season 6","exp":"100x","drop":"40%","players_online":42,"players_total":1200}
 *
 * Las tasas (exp, drop, season) son configuración del servidor y se editan acá directamente.
 * El conteo de online y total se obtiene de la DB en tiempo real.
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

// ── Configuración del servidor (editar según el servidor) ──
const SERVER_SEASON    = 'Season 6';
const SERVER_EXP_RATE  = '100x';
const SERVER_DROP_RATE = '40%';

try {
    $db   = Database::get();
    $repo = new AccountRepository($db);

    $online = $repo->getOnlineCount();

    // Conteo total de cuentas activas (no bloqueadas)
    $stmt  = $db->query('SELECT COUNT(*) FROM MEMB_INFO WHERE bloc_code = 0');
    $total = (int) $stmt->fetchColumn();

    echo json_encode([
        'season'         => SERVER_SEASON,
        'exp'            => SERVER_EXP_RATE,
        'drop'           => SERVER_DROP_RATE,
        'players_online' => $online,
        'players_total'  => $total,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno'], JSON_THROW_ON_ERROR);
}
