<?php
/**
 * GET /api/forum/search.php?q=término[&category_id=X]
 * Búsqueda en títulos y cuerpos (F-11.01). Público, solo lectura.
 *
 * Límite conocido: SQL Server Express sin Full-Text Search → LIKE con TOP
 * fijo (30 resultados), ordenado por actividad. Para la población actual del
 * foro alcanza; si crece, evaluar instalar FTS (documentado en el backlog).
 * Categorías ocultas: solo aparecen para admins (mismo criterio 404 que el
 * resto — no revelar existencia).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$term       = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category_id'] ?? 0);

if (mb_strlen($term) < ForumValidation::SEARCH_MIN_LEN) {
    http_response_code(422);
    echo json_encode(['error' => 'Escribí al menos ' . ForumValidation::SEARCH_MIN_LEN . ' caracteres para buscar.']);
    exit;
}

/** Recorte alrededor de la primera aparición del término (para el resaltado del cliente). */
function foroSnippet(string $text, string $term): string {
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    $pos  = mb_stripos($text, $term);
    if ($pos === false) return mb_substr($text, 0, 140);
    $start = max(0, $pos - 60);
    $snip  = mb_substr($text, $start, 160);
    return ($start > 0 ? '…' : '') . $snip . (mb_strlen($text) > $start + 160 ? '…' : '');
}

try {
    $repo    = new ForumRepository(ForumDatabase::get());
    $me      = optionalAuth();
    $isAdmin = isset($me['usr']) && isAdminAccount($me['usr']);

    $like    = '%' . ForumValidation::escapeLike($term) . '%';
    $threads = $repo->searchThreads($like, $categoryId, $isAdmin);

    // Hilos donde el match vive en una respuesta: una sola query extra para todos
    $sinMatchPropio = [];
    foreach ($threads as $t) {
        if (mb_stripos($t['title'], $term) === false && mb_stripos($t['body'], $term) === false) {
            $sinMatchPropio[] = $t['id'];
        }
    }
    $postBodies = $repo->getMatchingPostBodies($sinMatchPropio, $like);

    $results = array_map(function ($t) use ($term, $postBodies) {
        $source = mb_stripos($t['title'], $term) !== false || mb_stripos($t['body'], $term) !== false
            ? $t['body']
            : ($postBodies[$t['id']] ?? $t['body']);
        return [
            'id'                  => $t['id'],
            'category_id'         => $t['category_id'],
            'category_name'       => $t['category_name'],
            'title'               => $t['title'],
            'author_display_name' => $t['author_display_name'],
            'reply_count'         => $t['reply_count'],
            'last_post_at'        => $t['last_post_at'],
            'snippet'             => foroSnippet($source, $term),
        ];
    }, $threads);

    echo json_encode(['q' => $term, 'results' => $results], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
