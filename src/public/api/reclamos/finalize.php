<?php
/**
 * POST /api/reclamos/finalize.php  [requiere token]
 * Paso 3 del flujo (después de create.php o reply.php, y de subir cada
 * imagen vía upload_url.php): sella el MENSAJE con las URLs finales de
 * sus imágenes y recién acá notifica a Discord — con el número de ticket,
 * la carpeta en R2, el nick, el texto y las imágenes, todo junto.
 *
 * Body JSON: { "mensajeId": 456, "imagenes": ["https://.../reclamos/123/a.jpg", ...] }
 * (imagenes puede venir vacío si el jugador no adjuntó fotos — igual hay
 * que llamar finalize: es lo que dispara la notificación a Discord).
 *
 * Cada URL tiene que estar dentro de la carpeta reclamos/{reclamoId}/ del
 * ticket al que pertenece el mensaje — evita que se cuelen imágenes de
 * otra carpeta o de afuera del bucket en la notificación de Discord.
 *
 * El título del embed distingue ticket nuevo ("Nuevo reclamo #N") de
 * comentario de seguimiento ("Nuevo comentario en reclamo #N") según si
 * el mensaje es el primero del hilo.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/reclamos_db.php';
require_once SRC_ROOT . '/lib/R2Presign.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$mensajeId = isset($body['mensajeId']) ? (int) $body['mensajeId'] : 0;
$imagenes  = is_array($body['imagenes'] ?? null) ? array_slice($body['imagenes'], 0, 5) : [];

if ($mensajeId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta mensajeId.']); exit;
}

try {
    $db = ReclamosDatabase::get();

    $select = $db->prepare(
        "SELECT m.reclamo_id, m.mensaje,
                CASE WHEN m.id = (SELECT MIN(m2.id) FROM reclamos.mensajes m2
                                  WHERE m2.reclamo_id = m.reclamo_id)
                     THEN 1 ELSE 0 END AS es_primero
         FROM reclamos.mensajes m
         JOIN reclamos.reclamos r ON r.id = m.reclamo_id
         WHERE m.id = :id AND m.autor_tipo = 'jugador' AND m.autor_nick = :nick
           AND r.nick = :nick2 AND m.imagenes_json IS NULL"
    );
    $select->execute([':id' => $mensajeId, ':nick' => $auth['usr'], ':nick2' => $auth['usr']]);
    $row = $select->fetch();

    if ($row === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Mensaje no encontrado o ya finalizado.']);
        exit;
    }

    $reclamoId    = (int) $row['reclamo_id'];
    $mensaje      = (string) $row['mensaje'];
    $esPrimero    = (bool) $row['es_primero'];
    $folderPrefix = 'reclamos/' . $reclamoId . '/';
    $publicPrefix = R2Presign::normalizePublicUrl($_ENV['RECLAMOS_R2_PUBLIC_URL'] ?? '') . '/' . $folderPrefix;

    foreach ($imagenes as $url) {
        if (!is_string($url) || strpos($url, $publicPrefix) !== 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Alguna imagen no pertenece a este reclamo.']);
            exit;
        }
    }

    $update = $db->prepare('UPDATE reclamos.mensajes SET imagenes_json = :json WHERE id = :id');
    $update->execute([
        ':json' => json_encode($imagenes, JSON_THROW_ON_ERROR),
        ':id'   => $mensajeId,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo guardar el reclamo.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
    exit;
}

// Notificación a Discord — best-effort, nunca rompe la respuesta al cliente.
try {
    $webhook = $_ENV['DISCORD_WEBHOOK_RECLAMOS'] ?? '';
    if ($webhook === '') {
        error_log("reclamos: DISCORD_WEBHOOK_RECLAMOS vacío, no se notificó el reclamo #{$reclamoId}");
    } else {
        $embed = [
            'title'  => $esPrimero
                ? "Nuevo reclamo #{$reclamoId}"
                : "Nuevo comentario en reclamo #{$reclamoId}",
            'color'  => $esPrimero ? 0xE74C3C : 0xE67E22,
            'fields' => [
                ['name' => 'Nick', 'value' => $auth['usr'], 'inline' => true],
                ['name' => 'Carpeta R2', 'value' => $folderPrefix, 'inline' => true],
                ['name' => 'Mensaje', 'value' => mb_substr($mensaje, 0, 1024)],
            ],
            'timestamp' => gmdate('c'),
        ];
        if (!empty($imagenes[0])) {
            $embed['image'] = ['url' => $imagenes[0]];
        }
        if (count($imagenes) > 1) {
            $embed['fields'][] = [
                'name'  => 'Más imágenes',
                'value' => implode("\n", array_slice($imagenes, 1, 3)),
            ];
        }

        $payload = json_encode(['embeds' => [$embed]], JSON_THROW_ON_ERROR);
        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\n",
                'content'       => $payload,
                'timeout'       => 5,
                // Sin esto, file_get_contents devuelve false ante un 4xx/5xx
                // y se pierde el cuerpo de la respuesta (donde Discord explica
                // el error) — no se podía saber por qué fallaba.
                'ignore_errors' => true,
            ],
        ]);
        $result = @file_get_contents($webhook, false, $context);

        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) { $status = (int) $m[1]; break; }
        }

        if ($result === false) {
            error_log("reclamos: request a Discord falló (sin respuesta) para reclamo #{$reclamoId}");
        } elseif ($status !== 204) {
            error_log("reclamos: Discord respondió HTTP {$status} para reclamo #{$reclamoId}: {$result}");
        }
    }
} catch (Throwable $e) {
    error_log('reclamos: fallo al notificar a Discord: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'id' => $reclamoId], JSON_THROW_ON_ERROR);
