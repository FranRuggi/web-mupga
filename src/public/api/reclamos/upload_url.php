<?php
/**
 * POST /api/reclamos/upload_url.php  [requiere token]
 * Devuelve una presigned URL (PUT) para subir una imagen directamente a R2
 * desde el navegador, más la URL pública final del archivo.
 *
 * Body JSON: { "mensajeId": 456, "contentType": "image/jpeg" }
 * Las imágenes se atan a un MENSAJE del hilo (no al ticket): el mensaje
 * tiene que ser del jugador del token, pertenecer a un ticket suyo y no
 * estar finalizado (imagenes_json IS NULL) — así no se puede subir a la
 * carpeta de un reclamo ajeno ni agregar imágenes a mensajes ya enviados.
 *
 * El "filename" que mande el cliente NO se usa para nada — el nombre del
 * objeto en R2 se genera acá para evitar path traversal / overwrite.
 * La carpeta sigue siendo reclamos/{reclamoId}/ (por ticket, no por
 * mensaje) — así Discord y el staff encuentran todo lo del ticket junto.
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

$mensajeId   = isset($body['mensajeId']) ? (int) $body['mensajeId'] : 0;
$contentType = $body['contentType'] ?? '';

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if ($mensajeId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta mensajeId.']); exit;
}

if (!isset($allowed[$contentType])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de imagen no permitido. Solo JPG, PNG o WebP.']);
    exit;
}

try {
    // El join con la cabecera valida la propiedad del ticket completo, no
    // solo el autor del mensaje.
    $check = ReclamosDatabase::get()->prepare(
        "SELECT m.reclamo_id
         FROM reclamos.mensajes m
         JOIN reclamos.reclamos r ON r.id = m.reclamo_id
         WHERE m.id = :id AND m.autor_tipo = 'jugador' AND m.autor_nick = :nick
           AND r.nick = :nick2 AND m.imagenes_json IS NULL"
    );
    $check->execute([':id' => $mensajeId, ':nick' => $auth['usr'], ':nick2' => $auth['usr']]);
    $reclamoId = $check->fetchColumn();

    if ($reclamoId === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Mensaje no encontrado o ya finalizado.']);
        exit;
    }
    $reclamoId = (int) $reclamoId;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo verificar el mensaje.']);
    exit;
}

$bucket    = $_ENV['RECLAMOS_R2_BUCKET']     ?? '';
$accessKey = $_ENV['RECLAMOS_R2_ACCESS_KEY'] ?? '';
$secretKey = $_ENV['RECLAMOS_R2_SECRET_KEY'] ?? '';
$endpoint  = $_ENV['RECLAMOS_R2_ENDPOINT']   ?? '';
$publicUrl = R2Presign::normalizePublicUrl($_ENV['RECLAMOS_R2_PUBLIC_URL'] ?? '');

if ($bucket === '' || $accessKey === '' || $secretKey === '' || $endpoint === '' || $publicUrl === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Subida de imágenes no configurada.']);
    exit;
}

$objectKey = 'reclamos/' . $reclamoId . '/' . bin2hex(random_bytes(16)) . '.' . $allowed[$contentType];

try {
    $uploadUrl = R2Presign::presignPut(
        $endpoint,
        $bucket,
        $accessKey,
        $secretKey,
        $objectKey,
        $contentType,
        300 // 5 minutos
    );

    echo json_encode([
        'uploadUrl' => $uploadUrl,
        'publicUrl' => $publicUrl . '/' . $objectKey,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo generar la URL de subida.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
