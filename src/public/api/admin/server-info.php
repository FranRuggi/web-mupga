<?php
/**
 * /api/admin/server-info.php  [requiere admin]
 *
 * GET  → config_value crudo (string JSON) de config_key='secciones'.
 * POST → { "config_value": "<JSON de secciones>" }
 *        Valida que sea JSON bien formado con raíz {"secciones": [...]} antes
 *        de guardar — si es inválido rechaza con error claro, nunca guarda roto.
 *
 * Seguridad: POST-only para mutar; el Bearer token actúa como protección CSRF.
 * Transacción con UPDLOCK/HOLDLOCK sobre la fila del blob.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = requireAdmin();
$db    = AdminDatabase::get();

// ── GET: JSON crudo para el textarea del panel ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $db->prepare('SELECT config_value FROM dbo.server_info WHERE config_key = :k');
        $stmt->execute([':k' => 'secciones']);
        $raw = $stmt->fetchColumn();
        echo json_encode(['config_value' => ($raw === false ? null : $raw)], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body) || !isset($body['config_value']) || !is_string($body['config_value'])) {
    http_response_code(400); echo json_encode(['error' => 'Falta config_value (string JSON)']); exit;
}

$raw = trim($body['config_value']);

// Validación estricta: JSON bien formado y con el shape esperado
$parsed = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg() . '. No se guardó nada.']);
    exit;
}
if (!isset($parsed['secciones']) || !is_array($parsed['secciones'])) {
    http_response_code(400);
    echo json_encode(['error' => 'El JSON debe tener la raíz {"secciones": [...]}. No se guardó nada.']);
    exit;
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        'SELECT config_key FROM dbo.server_info WITH (UPDLOCK, HOLDLOCK) WHERE config_key = :k'
    );
    $stmt->execute([':k' => 'secciones']);
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $db->prepare(
            'UPDATE dbo.server_info
                SET config_value = :v, updated_by = :by, updated_at = GETDATE()
              WHERE config_key = :k'
        );
    } else {
        $stmt = $db->prepare(
            'INSERT INTO dbo.server_info (config_key, config_value, updated_by, updated_at)
             VALUES (:k, :v, :by, GETDATE())'
        );
    }
    $stmt->execute([':k' => 'secciones', ':v' => $raw, ':by' => $admin['usr']]);

    $db->commit();
    echo json_encode(['success' => true, 'secciones' => count($parsed['secciones'])], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
