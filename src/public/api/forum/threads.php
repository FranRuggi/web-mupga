<?php
/**
 * GET /api/forum/threads.php?category_id=X
 * Lista de hilos de una categoría (fijados primero, después por actividad
 * reciente). Público, solo lectura. Categoría oculta → 404 salvo admin.
 * author_account no se expone (privacidad — el cliente usa display_name).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once SRC_ROOT . '/lib/ForumBadges.php';
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
    $me       = optionalAuth();
    $isAdmin  = isset($me['usr']) && isAdminAccount($me['usr']);
    $category = $repo->getCategory($categoryId);

    if (!$category || ($category['is_hidden'] && !$isAdmin)) {
        http_response_code(404);
        echo json_encode(['error' => 'Categoría no encontrada.']);
        exit;
    }

    // Paginación (F-10.02): 25 por página, la URL del cliente refleja la página
    $perPage    = ForumValidation::THREADS_PER_PAGE;
    $total      = $repo->countThreadsByCategory($categoryId);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page       = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);

    $rows   = $repo->getThreadsByCategory($categoryId, $page, $perPage);
    $badges = ForumBadges::resolve($repo, array_column($rows, 'author_account'));

    $threads = array_map(function ($t) use ($badges) {
        $t['badges'] = $badges[$t['author_account']] ?? ['staff' => false, 'vip' => false];
        unset($t['author_account']);
        return $t;
    }, $rows);

    echo json_encode([
        'category'    => $category,
        'threads'     => $threads,
        'page'        => $page,
        'total_pages' => $totalPages,
        'total'       => $total,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
