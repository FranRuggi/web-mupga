<?php
/**
 * GET /api/forum/threads.php?category_id=X
 * Lista de hilos de una categoría (fijados primero, después por actividad
 * reciente). Público, solo lectura.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$categoryId = (int) ($_GET['category_id'] ?? 0);
if ($categoryId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta category_id.']);
    exit;
}

try {
    $repo     = new ForumRepository(ForumDatabase::get());
    $category = $repo->getCategory($categoryId);

    if (!$category) {
        http_response_code(404);
        echo json_encode(['error' => 'Categoría no encontrada.']);
        exit;
    }

    echo json_encode([
        'category' => $category,
        'threads'  => $repo->getThreadsByCategory($categoryId),
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
