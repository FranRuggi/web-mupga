<?php
/**
 * GET /api/prode/matches.php  [requiere token]
 * Devuelve todos los partidos con la predicción del usuario logueado.
 *
 * Respuesta:
 * {
 *   "upcoming": [...],
 *   "finished": [...]
 * }
 * Cada partido incluye el campo "prediction" (null si no predijo).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/prode_db.php';
require_once SRC_ROOT . '/db/ProdeRepository.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();

try {
    $repo = new ProdeRepository(ProdeDatabase::get(), Database::get());
    $data = $repo->getMatchesWithPredictions($auth['usr']);
    echo json_encode($data, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al obtener los partidos.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
