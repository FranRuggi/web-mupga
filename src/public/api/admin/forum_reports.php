<?php
/**
 * /api/admin/forum_reports.php  [requiere admin]
 *
 * GET  → cola de reportes pendientes (más viejos primero, con contexto)
 *        + últimas entradas del log de moderación.
 * POST → { action: "resolve", id, resolution: "accion"|"sin_merito", note? }
 *        Resuelve el reporte Y todos los demás pendientes sobre el mismo
 *        contenido (F-08.03). Queda en el log de auditoría.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = requireAdmin();
$repo  = new ForumRepository(ForumDatabase::get());

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        echo json_encode([
            'reports' => $repo->getPendingReports(),
            'log'     => $repo->getModerationLog(50),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (($body['action'] ?? '') !== 'resolve') {
    http_response_code(400); echo json_encode(['error' => 'Acción inválida: resolve']); exit;
}

$reportId   = (int) ($body['id'] ?? 0);
$resolution = (string) ($body['resolution'] ?? '');
$note       = trim((string) ($body['note'] ?? '')) ?: null;

if ($reportId <= 0 || !in_array($resolution, ['accion', 'sin_merito'], true)) {
    http_response_code(400); echo json_encode(['error' => 'Parámetros inválidos.']); exit;
}

try {
    // Buscar el reporte para conocer su target (y cerrar todos los del mismo contenido)
    $stmt = ForumDatabase::get()->prepare('SELECT target_type, target_id FROM forum.reports WHERE id = ?');
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();
    if (!$report) {
        http_response_code(404); echo json_encode(['error' => 'Reporte no encontrado.']); exit;
    }

    $status = $resolution === 'accion' ? 'resuelto_accion' : 'resuelto_sin_merito';
    $closed = $repo->resolveReportsForTarget($report['target_type'], (int) $report['target_id'], $status, $admin['usr'], $note);

    $repo->logModeration($admin['usr'], 'resolve_report', $report['target_type'], (string) $report['target_id'],
        ($resolution === 'accion' ? 'Acción tomada' : 'Sin mérito') . ($note ? " — {$note}" : ''));

    echo json_encode(['success' => true, 'closed' => $closed], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo resolver el reporte.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
