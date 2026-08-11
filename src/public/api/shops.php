<?php
/**
 * GET /api/shops.php
 * Radar de Tiendas: personajes con tienda personal abierta ahora mismo (solo lectura).
 *
 * MVP: no identifica el ítem en sí (nombre/nivel/opciones) — solo slot y precio en Zen.
 * Ver nota en src/db/ShopRepository.php.
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

try {
    $db = Database::get();
    $shopRepo = new ShopRepository($db);

    echo json_encode(['shops' => $shopRepo->getActiveShops()], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
