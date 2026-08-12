<?php
/**
 * POST /api/forum/upload_url.php  [requiere token]
 * Presigned URL (PUT) para subir una imagen del foro a R2 (F-04.05).
 * Body JSON: { "contentType": "image/jpeg", "thread_id": 12 (opcional) }
 *
 * Mismo patrón y MISMO BUCKET que reclamos/upload_url.php (un solo token y un
 * solo dominio que mantener), separados por carpeta: reclamos/{id} vs
 * foro/{id_hilo}. Al crear un hilo todavía no hay id → foro/nuevos. Guardas:
 * - Solo usuarios verificados (con personaje) — igual que publicar (F-09.02).
 * - Baneados del foro no suben.
 * - Cuota diaria por cuenta (forum.image_uploads) para que R2 no se
 *   convierta en hosting gratis.
 * - El nombre del objeto se genera acá (nada del cliente) — sin path
 *   traversal ni overwrite. El tamaño (5 MB) lo valida el cliente: la firma
 *   PUT no fija content-length (límite conocido, igual que Reclamos).
 *
 * El server solo renderiza como imagen las URLs bajo la carpeta foro/
 * (ForumValidation::restrictImages) — subir y abandonar deja un objeto
 * huérfano inofensivo (la cuota igual lo cuenta).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/R2Presign.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$contentType = $body['contentType'] ?? '';
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if (!isset($allowed[$contentType])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de imagen no permitido. Solo JPG, PNG o WebP.']);
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

try {
    $repo = new ForumRepository(ForumDatabase::get());

    if ($repo->isBanned($auth['usr'])) {
        http_response_code(403); echo json_encode(['error' => 'Estás baneado del foro.']); exit;
    }

    // Verificado = tiene personaje (mismo requisito que publicar, F-09.02)
    $charRepo = new CharacterRepository(Database::get());
    if ($charRepo->getMainCharacterName($auth['usr']) === null) {
        http_response_code(403);
        echo json_encode(['error' => 'Necesitás crear un personaje en el juego antes de subir imágenes.']);
        exit;
    }

    if ($repo->countImageUploadsToday($auth['usr']) >= ForumValidation::IMAGES_PER_DAY_MAX) {
        http_response_code(429);
        echo json_encode(['error' => 'Llegaste al límite de ' . ForumValidation::IMAGES_PER_DAY_MAX . ' imágenes por día.']);
        exit;
    }

    // Una subcarpeta por hilo. El id viene del cliente pero se castea a int:
    // no hay forma de armar una ruta arbitraria ni salir de foro/.
    $threadId  = (int) ($body['thread_id'] ?? 0);
    $carpeta   = $threadId > 0 ? (string) $threadId : 'nuevos';
    $objectKey = 'foro/' . $carpeta . '/' . bin2hex(random_bytes(16)) . '.' . $allowed[$contentType];

    $uploadUrl = R2Presign::presignPut($endpoint, $bucket, $accessKey, $secretKey, $objectKey, $contentType, 300);

    $repo->logImageUpload($auth['usr'], $objectKey);

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
