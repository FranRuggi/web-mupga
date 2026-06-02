<?php
/**
 * CORS — Incluir al inicio de TODOS los api/*.php.
 *
 * Permite que el frontend en Cloudflare Pages llame a la API del VPS.
 *
 * MIGRACIÓN: Agregar los dominios reales en .env:
 *   CORS_ALLOWED_ORIGINS=https://mupga.pages.dev,https://mupga.com
 *
 * En desarrollo local, http://localhost y http://127.0.0.1 siempre están permitidos.
 */

$defaultOrigins  = ['http://localhost', 'http://127.0.0.1', 'http://localhost:80'];
$envOrigins      = array_filter(array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '')));
$allowedOrigins  = array_merge($defaultOrigins, $envOrigins);

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$requestOrigin}");
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
header('Access-Control-Max-Age: 86400');

// Preflight: el browser lo manda antes del request real
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
