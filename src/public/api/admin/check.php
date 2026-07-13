<?php
/**
 * GET /api/admin/check.php  [requiere token]
 * Devuelve si el usuario logueado es admin del ControlPanel.
 * A diferencia de requireAdmin(), NO corta con 403: responde
 * { "is_admin": bool, "role": string|null } — lo usa la nav para
 * mostrar u ocultar el acceso al panel.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/admin_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();

try {
    $stmt = AdminDatabase::get()->prepare(
        'SELECT role FROM dbo.admins WHERE memb___id = :id AND active = 1'
    );
    $stmt->execute([':id' => $auth['usr']]);
    $row = $stmt->fetch();

    echo json_encode([
        'is_admin' => (bool) $row,
        'role'     => $row['role'] ?? null,
    ], JSON_THROW_ON_ERROR);

} catch (PDOException $e) {
    // Ante error de DB, comportarse como no-admin (la nav simplemente no muestra el link)
    echo json_encode(['is_admin' => false, 'role' => null]);
}
