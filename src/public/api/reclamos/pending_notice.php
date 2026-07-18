<?php
/**
 * GET /api/reclamos/pending_notice.php  [requiere token]
 * Devuelve los tickets del jugador con novedades del staff sin ver
 * (no_leido = 1), para el banner site-wide de app.js. El banner ahora es
 * un link al hilo (/reclamos/?ver={id}) en lugar de mostrar la respuesta
 * inline: abrir el detalle es lo que marca la novedad como leída.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/reclamos_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$auth = requireAuth();

try {
    $stmt = ReclamosDatabase::get()->prepare(
        "SELECT id, estado FROM reclamos.reclamos
         WHERE nick = :nick AND no_leido = 1
         ORDER BY id ASC"
    );
    $stmt->execute([':nick' => $auth['usr']]);
    $rows = $stmt->fetchAll();

    echo json_encode(['pendientes' => $rows], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar los reclamos.']);
}
