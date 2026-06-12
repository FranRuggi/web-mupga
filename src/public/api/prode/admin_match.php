<?php
/**
 * POST /api/prode/admin_match.php  [requiere X-Admin-Token]
 * Crea un nuevo partido.
 *
 * Body JSON:
 * {
 *   "team_home": "Argentina",
 *   "team_away": "Brasil",
 *   "match_datetime_utc": "2026-06-15T18:00:00",
 *   "stage": "Fase de Grupos"
 * }
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/prode_db.php';
require_once SRC_ROOT . '/db/ProdeRepository.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

requireAdmin();

$body      = json_decode(file_get_contents('php://input'), true);
$teamHome  = trim($body['team_home']           ?? '');
$teamAway  = trim($body['team_away']           ?? '');
$datetime  = trim($body['match_datetime_utc']  ?? '');
$stage     = trim($body['stage']               ?? '');

if (!$teamHome || !$teamAway || !$datetime || !$stage) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos: team_home, team_away, match_datetime_utc, stage.']);
    exit;
}

// Validar que la fecha sea parseable
if (!strtotime($datetime)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha inválido. Usar ISO 8601 (ej: 2026-06-15T18:00:00).']);
    exit;
}

try {
    $repo = new ProdeRepository(ProdeDatabase::get(), Database::get());
    $id   = $repo->createMatch($teamHome, $teamAway, $datetime, $stage);
    echo json_encode(['message' => 'Partido creado.', 'id' => $id], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al crear el partido.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}

// ── Helper admin ──────────────────────────────────────────────
function requireAdmin(): void {
    $expected = $_ENV['ADMIN_TOKEN'] ?? '';
    if ($expected === '') {
        http_response_code(503);
        echo json_encode(['error' => 'ADMIN_TOKEN no configurado en el servidor.']);
        exit;
    }
    $provided = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    if (!hash_equals($expected, $provided)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token de administrador inválido.']);
        exit;
    }
}
