<?php
/**
 * MuPGA — Generador de dist/ estático para Cloudflare Pages.
 *
 * Uso:  php build.php
 *
 * Genera dist/ con HTML estático de cada página PHP.
 * Los assets (CSS/JS/img) se copian a dist/assets/.
 * El backend (PHP/API) sigue viviendo en el VPS.
 */

$root   = __DIR__;
$dist   = $root . '/dist';
$runner = $root . '/build_runner.php';
$php    = PHP_BINARY;

// ── Páginas a renderizar ─────────────────────────────────────
$pages = [
    'index.html'            => 'src/public/index.php',
    'rankings/index.html'   => 'src/public/rankings/index.php',
    'info/index.html'       => 'src/public/info/index.php',
    'news/index.html'       => 'src/public/news/index.php',
    'downloads/index.html'  => 'src/public/downloads/index.php',
    'login/index.html'      => 'src/public/login/index.php',
    'register/index.html'   => 'src/public/register/index.php',
    'usercp/index.html'     => 'src/public/usercp/index.php',
    'guild/index.html'      => 'src/public/guild/index.php',
    'player/index.html'     => 'src/public/player/index.php',
];

// ── Helpers ──────────────────────────────────────────────────
function ensureDir(string $path): void {
    if (!is_dir($path)) mkdir($path, 0755, true);
}

function copyDir(string $src, string $dst): int {
    $count = 0;
    if (!is_dir($src)) return 0;
    ensureDir($dst);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        $rel    = substr($file->getPathname(), strlen($src) + 1);
        $target = $dst . '/' . $rel;
        ensureDir(dirname($target));
        copy($file->getPathname(), $target);
        $count++;
    }
    return $count;
}

function verify(string $html, string $dest): void {
    $configPos = strpos($html, 'config.js');
    $appPos    = strpos($html, 'app.js');
    if ($configPos === false) {
        echo "  ⚠  config.js no encontrado en $dest\n";
    } elseif ($configPos > $appPos) {
        echo "  ⚠  config.js va DESPUÉS de app.js en $dest — revisar layout.php\n";
    } else {
        echo "  ✓  config.js antes de app.js\n";
    }
}

// ── Renderizar páginas ───────────────────────────────────────
echo "=== MuPGA build ===\n\n";
ensureDir($dist);

foreach ($pages as $dest => $src) {
    $srcPath = $root . '/' . $src;
    echo "→ $dest\n";

    if (!file_exists($srcPath)) {
        echo "  SKIP (no existe: $src)\n\n";
        continue;
    }

    // Ejecutar en proceso aislado para que define() y globals sean frescos
    $cmd  = escapeshellarg($php) . ' ' . escapeshellarg($runner) . ' ' . escapeshellarg($srcPath);
    $html = shell_exec($cmd . ' 2>&1');    // capturar stderr también para debug

    if (empty(trim($html ?? ''))) {
        echo "  ERROR: salida vacía — revisar errores PHP\n\n";
        continue;
    }

    // Si hay errores PHP mezclados en la salida, advertir pero guardar igual
    if (str_contains($html, 'Fatal error') || str_contains($html, 'Parse error')) {
        echo "  ERROR PHP detectado en la salida:\n";
        echo '  ' . substr(trim($html), 0, 200) . "\n\n";
        continue;
    }

    $destPath = $dist . '/' . $dest;
    ensureDir(dirname($destPath));
    file_put_contents($destPath, $html);

    verify($html, $dest);
    echo '  ' . number_format(strlen($html)) . " bytes → dist/$dest\n\n";
}

// ── Copiar assets ────────────────────────────────────────────
echo "→ assets/\n";
$n = copyDir($root . '/src/public/assets', $dist . '/assets');
echo "  $n archivos copiados a dist/assets/\n\n";

// ── Resumen ──────────────────────────────────────────────────
$built = glob($dist . '/**/*.html');
$built = array_merge($built, glob($dist . '/*.html'));
echo "=== Listo: " . count($built) . " páginas en dist/ ===\n";
echo "Siguientes pasos:\n";
echo "  1. Subir dist/ a Cloudflare Pages (apunta a este directorio)\n";
echo "  2. Verificar que config.js tenga la URL real del VPS\n";
echo "  3. Configurar CORS_ALLOWED_ORIGINS en el .env del VPS\n";
