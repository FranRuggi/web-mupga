<?php
/**
 * Invocado por build.php — proceso aislado por página.
 * argv[1] = ruta absoluta al archivo PHP a renderizar
 */
$srcFile = $argv[1] ?? '';
if (!$srcFile || !file_exists($srcFile)) {
    fwrite(STDERR, "build_runner: archivo no encontrado: $srcFile\n");
    exit(1);
}

// Simular entorno web mínimo para que bootstrap y layout carguen sin errores
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['HTTP_HOST']      = '';
$_SERVER['HTTPS']          = 'off';
$_SERVER['DOCUMENT_ROOT']  = '';
$_GET    = [];
$_POST   = [];
$_COOKIE = [];

ob_start();
include $srcFile;
echo ob_get_clean();
