<?php
/**
 * GET /api/prode/ranking.php  [público]
 * Devuelve el top 50 de jugadores del prode.
 *
 * Respuesta: array de objetos con account, total_points, exact_hits, winner_hits.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/prode_db.php';
require_once SRC_ROOT . '/db/ProdeRepository.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

try {
    $repo = new ProdeRepository(ProdeDatabase::get(), Database::get());
    echo json_encode($repo->getRanking(), JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al obtener el ranking.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
