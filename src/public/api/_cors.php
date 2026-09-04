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

// Lockdown server-side: con overlay activo, toda la API responde 503
// (salvo status/hora/admin/login — ver src/lib/Lockdown.php).
require_once SRC_ROOT . '/lib/Lockdown.php';
enforceLockdown();

// Apertura: hasta la hora de apertura la API responde 503 salvo
// registro/login/status/hora/admin (ver src/lib/AperturaGate.php).
// Se desactiva sola al pasar la hora objetivo.
//
// Los is_file() no son paranoia: esto corre en el choke point de TODA la API,
// así que un archivo faltante (deploy a medias) no puede dejar el sitio sin
// login. Los require van acá arriba y no adentro de enforceApertura() para
// que la config quede cargada en el scope global.
$aperturaCfg = SRC_ROOT . '/config/apertura.php';
$aperturaLib = SRC_ROOT . '/lib/AperturaGate.php';
if (is_file($aperturaCfg) && is_file($aperturaLib)) {
    require_once $aperturaCfg;
    require_once $aperturaLib;
    enforceApertura();
}
