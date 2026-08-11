<?php
/**
 * GET /api/forum/thread.php?id=X
 * Detalle de un hilo: mensaje de apertura + respuestas + reacciones.
 * Público, solo lectura. Si viene un token válido (opcional), marca qué
 * mensajes ya reaccionó el visitante.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
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
    $thread = $repo->getThread($id);

    if (!$thread) {
        http_response_code(404);
        echo json_encode(['error' => 'Hilo no encontrado.']);
        exit;
    }

    $posts    = $repo->getPostsByThread($id);
    $postIds  = array_map(fn($p) => (int) $p['id'], $posts);

    $reactionCounts = $repo->getReactionCounts('post', $postIds);
    $threadCount    = $repo->getReactionCounts('thread', [$id]);

    $me = optionalAuth();
    $myPostReactions   = $me ? $repo->getUserReactedTargets('post', $postIds, $me['usr']) : [];
    $myThreadReacted   = $me ? in_array($id, $repo->getUserReactedTargets('thread', [$id], $me['usr']), true) : false;

    $thread['reactions'] = [
        'count'    => $threadCount[$id] ?? 0,
        'reacted'  => $myThreadReacted,
    ];

    $posts = array_map(function ($p) use ($reactionCounts, $myPostReactions) {
        $pid = (int) $p['id'];
        $p['reactions'] = [
            'count'   => $reactionCounts[$pid] ?? 0,
            'reacted' => in_array($pid, $myPostReactions, true),
        ];
        return $p;
    }, $posts);

    echo json_encode([
        'thread' => $thread,
        'posts'  => $posts,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
