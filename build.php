<?php
/**
 * MuPGA — Generador de dist/ estático para Cloudflare Pages.
 *
 * Uso:
 *   php build.php                     → construye TODOS los targets
 *   php build.php --target=landing    → construye uno solo
 *
 * Cada target es un SITIO independiente con su propio proyecto de Cloudflare
 * Pages y su propio dominio, pero comparte el código de este repo (design
 * system, layout, app.js, auth.js). Salida: dist/<target>/.
 *
 *   landing    → mupga.com.ar             (selector de servidores)
 *   servidor1  → servidor1.mupga.com.ar   (el sitio actual del servidor)
 *   foro       → foro.mupga.com.ar        (Etapa 2 — todavía sin páginas)
 *
 * El backend (PHP/API) sigue viviendo en el VPS; acá solo se genera HTML.
 */

$root   = __DIR__;
$runner = $root . '/build_runner.php';
$php    = PHP_BINARY;

// ── Targets ──────────────────────────────────────────────────
// 'pages'          → destino dentro de dist/<target>/ => archivo PHP fuente
// 'assets_exclude' → subcarpetas de assets/ que este sitio NO necesita.
//                    Importante: img/shop son ~970 gifs de íconos de ítems;
//                    copiarlos a la landing infla el deploy sin motivo
//                    (Cloudflare Pages limita a 20.000 archivos por deploy).
$targets = [

    'landing' => [
        'pages' => [
            'index.html' => 'src/public/landing/index.php',
        ],
        'assets_exclude' => ['img/shop', 'img/slider', 'img/class', 'img/currencies'],
    ],

    'servidor1' => [
        'pages' => [
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
            'donate/index.html'         => 'src/public/donate/index.php',
            'donate/success/index.html' => 'src/public/donate/success/index.php',
            'donate/error/index.html'   => 'src/public/donate/error/index.php',
            'mudial/index.html'         => 'src/public/mudial/index.php',
            'reclamos/index.html'       => 'src/public/reclamos/index.php',
            'donate2/index.html'        => 'src/public/donate2/index.php',
            'controlpanel/index.html'   => 'src/public/controlpanel/index.php',
            'tienda/index.html'         => 'src/public/tienda/index.php',
            'privacy/index.html'        => 'src/public/privacy/index.php',
            'terms/index.html'          => 'src/public/terms/index.php',
        ],
        'assets_exclude' => [],
    ],

    // Etapa 2 — se llena cuando existan las páginas del foro.
    'foro' => [
        'pages'          => [],
        'assets_exclude' => ['img/shop', 'img/slider', 'img/class', 'img/currencies'],
    ],
];

// ── Argumentos ───────────────────────────────────────────────
$only = null;
foreach ($argv as $arg) {
    if (preg_match('/^--target=(.+)$/', $arg, $m)) $only = $m[1];
}

if ($only !== null && !isset($targets[$only])) {
    fwrite(STDERR, "Target desconocido: {$only}\nDisponibles: " . implode(', ', array_keys($targets)) . "\n");
    exit(1);
}

$toBuild = $only !== null ? [$only => $targets[$only]] : $targets;

// ── Helpers ──────────────────────────────────────────────────
function ensureDir(string $path): void {
    if (!is_dir($path)) mkdir($path, 0755, true);
}

/**
 * Copia src → dst recursivamente. $exclude son rutas relativas a $src
 * (ej. 'img/shop'): se saltea todo lo que cuelgue de ahí.
 */
function copyDir(string $src, string $dst, array $exclude = []): int {
    $count = 0;
    if (!is_dir($src)) return 0;
    ensureDir($dst);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($src) + 1));

        foreach ($exclude as $skip) {
            if (str_starts_with($rel, rtrim($skip, '/') . '/')) continue 2;
        }

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
    } elseif ($appPos !== false && $configPos > $appPos) {
        echo "  ⚠  config.js va DESPUÉS de app.js en $dest — revisar layout.php\n";
    } else {
        echo "  ✓  config.js antes de app.js\n";
    }
}

// ── Build ────────────────────────────────────────────────────
echo "=== MuPGA build ===\n";
echo 'Targets: ' . implode(', ', array_keys($toBuild)) . "\n\n";

$errores = 0;

foreach ($toBuild as $target => $cfg) {
    $dist = $root . '/dist/' . $target;

    echo "══ Target: {$target} ══════════════════════════════\n";

    if (empty($cfg['pages'])) {
        echo "  (sin páginas todavía — se saltea)\n\n";
        continue;
    }

    ensureDir($dist);

    foreach ($cfg['pages'] as $dest => $src) {
        $srcPath = $root . '/' . $src;
        echo "→ $dest\n";

        if (!file_exists($srcPath)) {
            echo "  SKIP (no existe: $src)\n\n";
            continue;
        }

        // Proceso aislado por página: define() y globals frescos en cada una
        $cmd  = escapeshellarg($php) . ' ' . escapeshellarg($runner) . ' ' . escapeshellarg($srcPath);
        $html = shell_exec($cmd . ' 2>&1');    // capturar stderr también para debug

        if (empty(trim($html ?? ''))) {
            echo "  ERROR: salida vacía — revisar errores PHP\n\n";
            $errores++;
            continue;
        }

        if (str_contains($html, 'Fatal error') || str_contains($html, 'Parse error')) {
            echo "  ERROR PHP detectado en la salida:\n";
            echo '  ' . substr(trim($html), 0, 200) . "\n\n";
            $errores++;
            continue;
        }

        $destPath = $dist . '/' . $dest;
        ensureDir(dirname($destPath));
        file_put_contents($destPath, $html);

        verify($html, $dest);
        echo '  ' . number_format(strlen($html)) . " bytes → dist/$target/$dest\n\n";
    }

    echo "→ assets/\n";
    $n = copyDir($root . '/src/public/assets', $dist . '/assets', $cfg['assets_exclude']);
    echo "  $n archivos copiados a dist/$target/assets/";
    if (!empty($cfg['assets_exclude'])) {
        echo ' (excluido: ' . implode(', ', $cfg['assets_exclude']) . ')';
    }
    echo "\n\n";

    echo "→ _headers\n";
    file_put_contents($dist . '/_headers', "/*\n  Cache-Control: no-cache, must-revalidate\n");
    echo "  Cache-Control: no-cache, must-revalidate para /*\n\n";
}

// ── Resumen ──────────────────────────────────────────────────
echo "=== Resumen ===\n";
foreach ($toBuild as $target => $cfg) {
    if (empty($cfg['pages'])) continue;
    $dir = $root . '/dist/' . $target;
    $n   = count(glob($dir . '/*.html')) + count(glob($dir . '/**/*.html'));
    echo "  {$target}: {$n} páginas en dist/{$target}/\n";
}

if ($errores > 0) {
    echo "\n⚠  {$errores} página(s) con error — el deploy NO debería subir esto.\n";
    exit(1);   // corta el workflow de GitHub Actions en vez de publicar un sitio roto
}

echo "\nListo.\n";
