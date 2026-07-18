<?php
/**
 * GET /api/reclamos/pending_notice.php  [requiere token]
 * Devuelve el reclamo resuelto más viejo que el jugador todavía no vio
 * (estado='resuelto' AND leido=0), o { reclamo: null } si no tiene ninguno.
 * Se consulta en todas las páginas del sitio para mostrar el banner
 * (ver loadReclamoNotice() en app.js), igual que site/status.php.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/reclamos_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$auth = requireAuth();

try {
    $stmt = ReclamosDatabase::get()->prepare(
        "SELECT TOP 1 id, mensaje, respuesta, respondido_en
         FROM reclamos.reclamos
         WHERE nick = :nick AND estado = 'resuelto' AND leido = 0
         ORDER BY respondido_en ASC"
    );
    $stmt->execute([':nick' => $auth['usr']]);
    $row = $stmt->fetch();

    echo json_encode(['reclamo' => $row ?: null], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar el reclamo.']);
}
