<?php
/**
 * POST /api/admin/upload.php  [requiere admin]
 * Sube una imagen para noticias (multipart/form-data, campo "image").
 * Guarda en src/public/uploads/news/ del VPS con nombre aleatorio y
 * devuelve { "url": "https://.../uploads/news/xxxx.jpg" }.
 *
 * Validaciones: máx 3 MB; solo JPG/PNG/WebP/GIF (MIME real via finfo,
 * no la extensión que diga el cliente).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$admin = requireAdmin();

if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No llegó ninguna imagen (campo "image") o falló la subida.']); exit;
}

$file = $_FILES['image'];

if ($file['size'] > 3 * 1024 * 1024) {
    http_response_code(400); echo json_encode(['error' => 'La imagen supera los 3 MB.']); exit;
}

// MIME real del contenido, no confiar en el nombre del archivo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$exts  = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
if (!isset($exts[$mime])) {
    http_response_code(400);
    echo json_encode(['error' => "Formato no permitido ({$mime}). Solo JPG, PNG, WebP o GIF."]); exit;
}

$dir = SRC_ROOT . '/public/uploads/news';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    http_response_code(500); echo json_encode(['error' => 'No se pudo crear el directorio de uploads.']); exit;
}

$name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $exts[$mime];

if (!move_uploaded_file($file['tmp_name'], "{$dir}/{$name}")) {
    http_response_code(500); echo json_encode(['error' => 'No se pudo guardar la imagen.']); exit;
}

// URL absoluta servida por este mismo host (el VPS de la API)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
$pubDir  = rtrim(str_replace('\\', '/', realpath(SRC_ROOT . '/public')), '/');
$webPath = str_replace($docRoot, '', $pubDir);

echo json_encode(['url' => "{$scheme}://{$host}{$webPath}/uploads/news/{$name}"], JSON_THROW_ON_ERROR);
