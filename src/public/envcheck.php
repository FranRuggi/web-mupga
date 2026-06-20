<?php
// ARCHIVO TEMPORAL DE DIAGNÓSTICO — borrar después de usar

define('SRC_ROOT', dirname(__DIR__));
define('PROJECT_ROOT', dirname(SRC_ROOT));

$envPath = PROJECT_ROOT . '/.env';

echo "<pre>";
echo "PROJECT_ROOT  : " . PROJECT_ROOT . "\n";
echo "Ruta .env     : " . $envPath . "\n";
echo "file_exists   : " . (file_exists($envPath) ? 'SI' : 'NO') . "\n";
echo "is_readable   : " . (is_readable($envPath) ? 'SI' : 'NO') . "\n\n";

// Antes de cargar el .env, qué tiene $_ENV?
echo "PAYMENTS_API_URL (antes de loadEnv): " . ($_ENV['PAYMENTS_API_URL'] ?? '(vacío)') . "\n";

// Cargar .env manualmente
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        $len = strlen($v);
        if ($len >= 2) {
            $f = $v[0]; $l = $v[$len - 1];
            if (($f === '"' && $l === '"') || ($f === "'" && $l === "'")) $v = substr($v, 1, -1);
        }
        if ($k === 'PAYMENTS_API_URL') {
            echo "Línea encontrada en .env: PAYMENTS_API_URL=" . $v . "\n";
        }
    }
}

// Buscar en getenv también
echo "\ngetenv('PAYMENTS_API_URL') : " . (getenv('PAYMENTS_API_URL') ?: '(vacío)') . "\n";
echo "</pre>";
