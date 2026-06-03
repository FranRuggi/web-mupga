<?php
/**
 * GET /api/online.php
 * Devuelve la cantidad de jugadores conectados.
 * Respuesta: {"count": 42}
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

try {
    $db    = Database::get();
    $repo  = new AccountRepository($db);
    $count = $repo->getOnlineCount();

    echo json_encode(['count' => $count], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno'], JSON_THROW_ON_ERROR);
}
