<?php
/**
 * /api/admin/site-status.php  [requiere admin]
 *
 * GET  → estado actual (fila id=1) + presets disponibles.
 * POST → actualizar el estado. Body JSON:
 *   { "is_active": 0|1, "mode": "banner"|"overlay", "title": "...",
 *     "message": "...", "scheduled_end": "YYYY-MM-DD HH:MM"|null,
 *     "preset_key": "..." (opcional — pisa title/message con el preset) }
 *
 * Seguridad: POST-only para mutar; el Bearer token actúa como protección
 * CSRF (no viaja automáticamente como una cookie). Transacción con
 * UPDLOCK/HOLDLOCK: varios admins pueden tocar site_status a la vez.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = requireAdmin();
$db    = AdminDatabase::get();

// ── GET: estado actual + presets ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $status = $db->query(
            "SELECT is_active, mode, title, message,
                    CONVERT(varchar(16), scheduled_end, 120) AS scheduled_end,
                    updated_by, CONVERT(varchar(19), updated_at, 120) AS updated_at
               FROM dbo.site_status WHERE id = 1"
        )->fetch();

        $presets = $db->query(
            'SELECT preset_key, title, message FROM dbo.status_presets ORDER BY preset_key'
        )->fetchAll();

        echo json_encode(['status' => $status, 'presets' => $presets], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

// ── POST: actualizar ─────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400); echo json_encode(['error' => 'Body JSON inválido']); exit;
}

$isActive = (int) ($body['is_active'] ?? 0);
$mode     = $body['mode'] ?? 'banner';
$title    = trim((string) ($body['title'] ?? ''));
$message  = trim((string) ($body['message'] ?? ''));
$schedEnd = $body['scheduled_end'] ?? null;
$preset   = trim((string) ($body['preset_key'] ?? ''));

// Allowlist estricta de modos — nada más se acepta
if (!in_array($mode, ['banner', 'overlay'], true)) {
    http_response_code(400); echo json_encode(['error' => "Modo inválido: solo 'banner' u 'overlay'"]); exit;
}
if (!in_array($isActive, [0, 1], true)) {
    http_response_code(400); echo json_encode(['error' => 'is_active debe ser 0 o 1']); exit;
}
// scheduled_end: null o 'YYYY-MM-DD HH:MM(:SS)'
if ($schedEnd !== null && $schedEnd !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2})?$/', (string) $schedEnd)) {
        http_response_code(400); echo json_encode(['error' => 'scheduled_end inválido (formato YYYY-MM-DD HH:MM)']); exit;
    }
    $schedEnd = str_replace('T', ' ', (string) $schedEnd);
} else {
    $schedEnd = null;
}

try {
    $db->beginTransaction();

    // Lock de la fila única — evita pisadas entre admins concurrentes
    $stmt = $db->prepare('SELECT id FROM dbo.site_status WITH (UPDLOCK, HOLDLOCK) WHERE id = 1');
    $stmt->execute();
    if (!$stmt->fetch()) {
        $db->rollBack();
        http_response_code(500); echo json_encode(['error' => 'No existe la fila site_status id=1']); exit;
    }

    // Preset: pisa title/message con lo guardado (dentro de la transacción)
    if ($preset !== '') {
        $stmt = $db->prepare('SELECT title, message FROM dbo.status_presets WHERE preset_key = :k');
        $stmt->execute([':k' => $preset]);
        $p = $stmt->fetch();
        if (!$p) {
            $db->rollBack();
            http_response_code(400); echo json_encode(['error' => "Preset desconocido: {$preset}"]); exit;
        }
        $title   = $p['title'];
        $message = $p['message'];
    }

    if ($isActive === 1 && ($title === '' || $message === '')) {
        $db->rollBack();
        http_response_code(400); echo json_encode(['error' => 'title y message son obligatorios para activar el aviso']); exit;
    }

    $stmt = $db->prepare(
        'UPDATE dbo.site_status
            SET is_active = :a, mode = :m, title = :t, message = :msg,
                scheduled_end = :se, updated_by = :by, updated_at = GETDATE()
          WHERE id = 1'
    );
    $stmt->execute([
        ':a' => $isActive, ':m' => $mode, ':t' => $title, ':msg' => $message,
        ':se' => $schedEnd, ':by' => $admin['usr'],
    ]);

    $db->commit();
    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
