<?php
/**
 * POST /api/reclamos/upload_url.php  [requiere token]
 * Devuelve una presigned URL (PUT) para subir una imagen directamente a R2
 * desde el navegador, más la URL pública final del archivo.
 *
 * Body JSON: { "reclamoId": 123, "contentType": "image/jpeg" }
 * El reclamo tiene que existir, pertenecerle al usuario del token y todavía
 * no estar finalizado (imagenes_json IS NULL) — así no se puede subir a la
 * carpeta de un reclamo ajeno ni agregar imágenes después de enviado.
 *
 * El "filename" que mande el cliente NO se usa para nada — el nombre del
 * objeto en R2 se genera acá para evitar path traversal / overwrite.
 * La carpeta es reclamos/{reclamoId}/ — así Discord puede mostrar
 * directamente dónde están las fotos de cada reclamo.
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

$reclamoId   = isset($body['reclamoId']) ? (int) $body['reclamoId'] : 0;
$contentType = $body['contentType'] ?? '';

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if ($reclamoId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta reclamoId.']); exit;
}

if (!isset($allowed[$contentType])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de imagen no permitido. Solo JPG, PNG o WebP.']);
    exit;
}

try {
    $check = ReclamosDatabase::get()->prepare(
        'SELECT TOP 1 1 FROM reclamos.reclamos
         WHERE id = :id AND nick = :nick AND imagenes_json IS NULL'
    );
    $check->execute([':id' => $reclamoId, ':nick' => $auth['usr']]);
    if ($check->fetchColumn() === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Reclamo no encontrado o ya finalizado.']);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo verificar el reclamo.']);
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
