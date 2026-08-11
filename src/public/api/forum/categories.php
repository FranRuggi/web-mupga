<?php
/**
 * GET /api/forum/categories.php
 * Lista de categorías del foro con conteo de hilos y última actividad.
 * Público, solo lectura. Las ocultas solo se incluyen para admins.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $repo    = new ForumRepository(ForumDatabase::get());
    $me      = optionalAuth();
    $isAdmin = isset($me['usr']) && isAdminAccount($me['usr']);

    echo json_encode(
        ['categories' => $repo->getCategories($isAdmin)],
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
