<?php
/**
 * GET /api/forum/thread.php?id=X
 * Detalle de un hilo: mensaje de apertura + respuestas + reacciones.
 * Público, solo lectura. Con token válido (opcional) marca reacciones
 * propias y qué mensajes son propios (is_mine).
 *
 * Privacidad: author_account NO se expone en la respuesta (es la cuenta de
 * login) — el frontend usa is_mine, calculado acá contra la sesión.
 * Posts borrados: viajan con is_deleted=true y el cuerpo vacío (placeholder
 * en el cliente); hilos borrados u ocultos → 404.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta id.']);
    exit;
}

try {
    $repo   = new ForumRepository(ForumDatabase::get());
    $me     = optionalAuth();
    $myAcc  = $me['usr'] ?? null;
    $isAdmin = $myAcc !== null && isAdminAccount($myAcc);

    $thread = $repo->getThread($id);
    if (!$thread || $thread['is_deleted']) {
        http_response_code(404);
        echo json_encode(['error' => 'Hilo no encontrado.']);
        exit;
    }

    $category = $repo->getCategory($thread['category_id']);
    if ($category && $category['is_hidden'] && !$isAdmin) {
        // 404, no 403 — no revelar que la categoría existe (F-13.02)
        http_response_code(404);
        echo json_encode(['error' => 'Hilo no encontrado.']);
        exit;
    }

    // Paginación de respuestas (F-03.05): 20 por página. ?post=X resuelve
    // server-side en qué página cae ese post (permalinks #post-{id}).
    $perPage    = ForumValidation::POSTS_PER_PAGE;
    $total      = $repo->countPostsByThread($id);
    $totalPages = max(1, (int) ceil($total / $perPage));

    $page = (int) ($_GET['page'] ?? 0);
    if ($page <= 0 && ($postId = (int) ($_GET['post'] ?? 0)) > 0) {
        $page = $repo->getPostPage($id, $postId, $perPage) ?? 1;
    }
    $page = min(max(1, $page), $totalPages);

    $posts   = $repo->getPostsByThread($id, $page, $perPage);
    $postIds = array_map(fn($p) => (int) $p['id'], $posts);

    $reactionCounts = $repo->getReactionCounts('post', $postIds);
    $threadCount    = $repo->getReactionCounts('thread', [$id]);

    $myPostReactions = $myAcc ? $repo->getUserReactedTargets('post', $postIds, $myAcc) : [];
    $myThreadReacted = $myAcc && in_array($id, $repo->getUserReactedTargets('thread', [$id], $myAcc), true);

    $thread['reactions'] = ['count' => $threadCount[$id] ?? 0, 'reacted' => $myThreadReacted];
    $thread['is_mine']   = $myAcc !== null && $thread['author_account'] === $myAcc;
    unset($thread['author_account'], $thread['deleted_by']);

    $posts = array_map(function ($p) use ($reactionCounts, $myPostReactions, $myAcc) {
        $pid = (int) $p['id'];
        $p['reactions'] = [
            'count'   => $reactionCounts[$pid] ?? 0,
            'reacted' => in_array($pid, $myPostReactions, true),
        ];
        $p['is_mine'] = $myAcc !== null && $p['author_account'] === $myAcc;
        if ($p['is_deleted']) {
            $p['body'] = ''; // nunca filtrar el contenido borrado hacia afuera
        }
        unset($p['author_account'], $p['deleted_by']);
        return $p;
    }, $posts);

    echo json_encode([
        'thread'      => $thread,
        'posts'       => $posts,
        'page'        => $page,
        'total_pages' => $totalPages,
        'total_posts' => $total,
        'following'   => $myAcc !== null && $repo->isFollowing($myAcc, $id),
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
