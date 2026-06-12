<?php
/**
 * POST /api/prode/predict.php  [requiere token]
 * Guarda o actualiza la predicción del usuario para un partido.
 *
 * Body JSON: { "match_id": int, "score_home": int, "score_away": int }
 *
 * Validaciones (también en el frontend, pero backend es la fuente de verdad):
 * - El partido existe y está en status='pending'
 * - No está bloqueado (is_locked=0)
 * - Faltan más de 60 minutos para el inicio (UTC)
 * - Los goles son enteros no negativos
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

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$matchId   = isset($body['match_id'])   ? (int)$body['match_id']   : null;
$scoreHome = isset($body['score_home']) ? (int)$body['score_home'] : null;
$scoreAway = isset($body['score_away']) ? (int)$body['score_away'] : null;

if ($matchId === null || $scoreHome === null || $scoreAway === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos requeridos: match_id, score_home, score_away.']);
    exit;
}

if ($scoreHome < 0 || $scoreAway < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Los goles no pueden ser negativos.']);
    exit;
}

try {
    $repo = new ProdeRepository(ProdeDatabase::get(), Database::get());
    $repo->savePrediction($auth['usr'], $matchId, $scoreHome, $scoreAway);

    echo json_encode([
        'message'    => 'Predicción guardada.',
        'match_id'   => $matchId,
        'score_home' => $scoreHome,
        'score_away' => $scoreAway,
    ], JSON_THROW_ON_ERROR);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al guardar la predicción.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
