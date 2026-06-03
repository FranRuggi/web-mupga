<?php
/**
 * GET /api/infodata.php
 * Sirve el contenido de data/info.json (fuera del public, no accesible directo).
 * Respuesta: el JSON tal cual está en el archivo.
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=300'); // cacheable 5 min — el JSON no cambia seguido

$path = PROJECT_ROOT . '/data/info.json';

if (!is_readable($path)) {
    http_response_code(404);
    echo json_encode(['error' => 'Archivo de datos no encontrado']);
    exit;
}

// Validar que sea JSON válido antes de enviarlo
$raw = file_get_contents($path);
json_decode($raw);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'El archivo info.json tiene un error de sintaxis: ' . json_last_error_msg()]);
    exit;
}

echo $raw;
