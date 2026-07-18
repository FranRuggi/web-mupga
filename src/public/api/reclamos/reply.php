<?php
/**
 * POST /api/reclamos/reply.php  [requiere token]
 * Agrega un comentario de seguimiento del jugador a un ticket propio ya
 * existente. Si el ticket estaba 'resuelto', lo reabre automáticamente
 * (no hace falta un botón "Reabrir" aparte — comentar ya lo reabre).
 *
 * Body JSON: { "reclamoId": 123, "mensaje": "..." }
 * Devuelve { mensajeId } — con eso el cliente sube imágenes (opcional)
 * vía upload_url.php + finalize.php, igual que con el primer mensaje.
 *
 * Cooldown corto (30s) por ticket para evitar flood accidental o
 * malicioso del hilo — no es el rate limit de creación de tickets nuevos
 * (ese vive en create.php), es más laxo a propósito.
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

$reclamoId = isset($body['reclamoId']) ? (int) $body['reclamoId'] : 0;
$mensaje   = trim((string) ($body['mensaje'] ?? ''));

if ($reclamoId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta reclamoId.']); exit;
}
if ($mensaje === '') {
    http_response_code(400); echo json_encode(['error' => 'El mensaje no puede estar vacío.']); exit;
}
if (mb_strlen($mensaje) > 2000) {
    http_response_code(400); echo json_encode(['error' => 'El mensaje supera los 2000 caracteres.']); exit;
}

$db = null;
try {
    $db = ReclamosDatabase::get();

    $owner = $db->prepare('SELECT TOP 1 1 FROM reclamos.reclamos WHERE id = :id AND nick = :nick');
    $owner->execute([':id' => $reclamoId, ':nick' => $auth['usr']]);
    if ($owner->fetchColumn() === false) {
        http_response_code(404); echo json_encode(['error' => 'Reclamo no encontrado.']); exit;
    }

    $rateLimitDisabled = ($_ENV['RECLAMOS_DISABLE_RATE_LIMIT'] ?? '') === 'true';

    if (!$rateLimitDisabled) {
        $cooldown = $db->prepare(
            "SELECT TOP 1 1 FROM reclamos.mensajes WITH (UPDLOCK, HOLDLOCK)
             WHERE reclamo_id = :id AND autor_tipo = 'jugador'
               AND created_at > DATEADD(SECOND, -30, DATEADD(HOUR, 3, GETDATE()))"
        );
        $cooldown->execute([':id' => $reclamoId]);
        if ($cooldown->fetchColumn() !== false) {
            http_response_code(429);
            echo json_encode(['error' => 'Esperá unos segundos antes de comentar de nuevo.']);
            exit;
        }
    }

    $db->beginTransaction();

    $insert = $db->prepare(
        "INSERT INTO reclamos.mensajes (reclamo_id, autor_tipo, autor_nick, mensaje, created_at)
         OUTPUT INSERTED.id
         VALUES (:reclamo_id, 'jugador', :nick, :mensaje, DATEADD(HOUR, 3, GETDATE()))"
    );
    $insert->execute([':reclamo_id' => $reclamoId, ':nick' => $auth['usr'], ':mensaje' => $mensaje]);
    $mensajeId = (int) $insert->fetchColumn();

    // Comentar reabre el ticket — el staff lo vuelve a ver como pendiente.
    $db->prepare("UPDATE reclamos.reclamos SET estado = 'nuevo' WHERE id = :id")
       ->execute([':id' => $reclamoId]);

    $db->commit();

    echo json_encode(['mensajeId' => $mensajeId], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    $payload = ['error' => 'No se pudo enviar el comentario.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
