<?php
/**
 * GET /api/reclamos/detalle.php?id=123  [requiere token]
 * Devuelve el ticket (si es del jugador logueado) con su hilo completo de
 * mensajes en orden cronológico. Abrir el detalle marca las novedades
 * como leídas (no_leido = 0) — es el equivalente del viejo mark_read.php,
 * que ya no existe como endpoint separado.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/reclamos_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$auth = requireAuth();
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta id.']); exit;
}

try {
    $db = ReclamosDatabase::get();

    $ticket = $db->prepare(
        'SELECT id, estado, no_leido, created_at
         FROM reclamos.reclamos WHERE id = :id AND nick = :nick'
    );
    $ticket->execute([':id' => $id, ':nick' => $auth['usr']]);
    $reclamo = $ticket->fetch();

    if ($reclamo === false) {
        http_response_code(404); echo json_encode(['error' => 'Reclamo no encontrado.']); exit;
    }

    $mensajes = $db->prepare(
        'SELECT id, autor_tipo, autor_nick, mensaje, imagenes_json, created_at
         FROM reclamos.mensajes WHERE reclamo_id = :id ORDER BY id ASC'
    );
    $mensajes->execute([':id' => $id]);
    $hilo = $mensajes->fetchAll();

    foreach ($hilo as &$m) {
        $m['imagenes'] = $m['imagenes_json'] ? (json_decode($m['imagenes_json'], true) ?: []) : [];
        unset($m['imagenes_json']);
    }
    unset($m);

    // Ver el hilo = enterarse de las novedades → se apaga el banner del sitio.
    if ((int) $reclamo['no_leido'] === 1) {
        $db->prepare('UPDATE reclamos.reclamos SET no_leido = 0 WHERE id = :id')
           ->execute([':id' => $id]);
        $reclamo['no_leido'] = 0;
    }

    echo json_encode(['reclamo' => $reclamo, 'mensajes' => $hilo], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo cargar el reclamo.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
