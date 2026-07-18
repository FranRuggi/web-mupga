<?php
/**
 * POST /api/reclamos/create.php  [requiere token]
 * Paso 1 del flujo: crea la fila del reclamo (sin imágenes todavía) y
 * devuelve el id, que después se usa como carpeta en R2
 * (reclamos/{id}/...) para subir las imágenes.
 *
 * Body JSON: { "mensaje": "..." }
 * El nick sale de la sesión (token), nunca del body.
 *
 * Rate limit: 1 reclamo cada 5 minutos por IP (hasheada con SHA2_256,
 * nunca se guarda la IP en texto plano). Ventana de tiempo con
 * DATEADD(HOUR, 3, GETDATE()) — NUNCA GETUTCDATE(), ver incidente
 * documentado en CLAUDE.md (GETUTCDATE() rota en este VPS).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/reclamos_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$mensaje = trim((string) ($body['mensaje'] ?? ''));
if ($mensaje === '') {
    http_response_code(400); echo json_encode(['error' => 'El mensaje no puede estar vacío.']); exit;
}
if (mb_strlen($mensaje) > 2000) {
    http_response_code(400); echo json_encode(['error' => 'El mensaje supera los 2000 caracteres.']); exit;
}

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ip = trim(explode(',', $ip)[0]);

$db = null;
try {
    $db = ReclamosDatabase::get();
    $db->beginTransaction();

    // WITH (UPDLOCK, HOLDLOCK) — mismo patrón anti-TOCTOU que el fix de
    // seguridad del prode: sin esto, dos requests simultáneos del mismo IP
    // podrían pasar el chequeo de rate limit a la vez.
    $check = $db->prepare(
        "SELECT TOP 1 1
         FROM reclamos.reclamos WITH (UPDLOCK, HOLDLOCK)
         WHERE ip_hash = HASHBYTES('SHA2_256', :ip)
           AND created_at > DATEADD(MINUTE, -5, DATEADD(HOUR, 3, GETDATE()))"
    );
    $check->execute([':ip' => $ip]);

    if ($check->fetchColumn() !== false) {
        $db->rollBack();
        http_response_code(429);
        echo json_encode(['error' => 'Ya mandaste un reclamo hace poco, esperá unos minutos.']);
        exit;
    }

    $insert = $db->prepare(
        "INSERT INTO reclamos.reclamos (nick, mensaje, ip_hash, created_at)
         OUTPUT INSERTED.id
         VALUES (:nick, :mensaje, HASHBYTES('SHA2_256', :ip), DATEADD(HOUR, 3, GETDATE()))"
    );
    $insert->execute([
        ':nick'    => $auth['usr'],
        ':mensaje' => $mensaje,
        ':ip'      => $ip,
    ]);
    $id = (int) $insert->fetchColumn();

    $db->commit();

    echo json_encode(['id' => $id], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    $payload = ['error' => 'No se pudo crear el reclamo.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
