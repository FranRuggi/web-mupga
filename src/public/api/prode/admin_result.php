<?php
/**
 * POST /api/prode/admin_result.php  [requiere X-Admin-Token]
 * Carga el resultado de un partido y aplica premios automáticamente.
 *
 * Body JSON: { "match_id": int, "score_home": int, "score_away": int }
 *
 * Efecto:
 * - Marca el partido como 'finished' y bloqueado
 * - Calcula puntos por predicción (3=exacto, 1=ganador, 0=fallo)
 * - Acredita WCoins y días VIP a los ganadores
 * - Si el partido ya fue resuelto, devuelve error 409
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
$matchId   = isset($body['match_id'])   ? (int)$body['match_id']   : null;
$scoreHome = isset($body['score_home']) ? (int)$body['score_home'] : null;
$scoreAway = isset($body['score_away']) ? (int)$body['score_away'] : null;

if ($matchId === null || $scoreHome === null || $scoreAway === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos: match_id, score_home, score_away.']);
    exit;
}

if ($scoreHome < 0 || $scoreAway < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Los goles no pueden ser negativos.']);
    exit;
}

try {
    $repo = new ProdeRepository(ProdeDatabase::get(), Database::get());
    $repo->resolveMatch($matchId, $scoreHome, $scoreAway);
    echo json_encode([
        'message'    => 'Resultado cargado y premios aplicados.',
        'match_id'   => $matchId,
        'score_home' => $scoreHome,
        'score_away' => $scoreAway,
    ], JSON_THROW_ON_ERROR);
} catch (RuntimeException $e) {
    http_response_code(409);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al procesar el resultado.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}

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
