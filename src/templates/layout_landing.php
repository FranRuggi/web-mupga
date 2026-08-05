<?php
/**
 * Layout de la LANDING (mupga.com.ar) — selector de servidores.
 * Variables esperadas: $pageTitle (string), $content (string)
 *
 * Distinto del layout del servidor (layout.php) a propósito:
 *   - Sin sidebar (jugadores online / stats): la landing no pertenece a
 *     ningún servidor en particular.
 *   - Sin nav de servidor (Rankings, Info, Tienda...): esos links son de
 *     cada subdominio.
 *   - Sin app.js ni auth.js: app.js dispara al cargar loadSiteStatus() y
 *     loadReclamoNotice(), que consultan el estado del SERVIDOR 1 — en un
 *     selector multi-servidor eso es ruido y dos requests inútiles.
 *     landing.js es autónomo y solo necesita config.js.
 */

// En modo CLI (build estático), base vacía — config.js maneja la URL del API.
if (php_sapi_name() === 'cli') {
    $base = '';
} else {
    $docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
    $pubDir  = rtrim(str_replace('\\', '/', realpath(SRC_ROOT . '/public')), '/');
    $webPath = str_replace($docRoot, '', $pubDir);
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base    = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $webPath;
}

$title = htmlspecialchars($pageTitle ?? 'MuPGA', ENT_QUOTES);
$year  = date('Y');

// Mismo cache-buster que layout.php: por request en dev, por deploy en build.
$v = (($_ENV['APP_ENV'] ?? 'production') === 'development')
    ? '?v=' . time()
    : '?v=' . date('YmdHi');
?>
<!DOCTYPE html>
<html lang="es" data-base-url="<?= $base ?>/">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="MuPGA — Servidores privados de MU Online Season 6. Elegí tu servidor y empezá a jugar.">
  <meta name="theme-color" content="#0d0b14">
  <link rel="icon" type="image/png" href="<?= $base ?>/assets/img/logoweb.png">
  <link rel="apple-touch-icon" href="<?= $base ?>/assets/img/logoweb.png">
  <title><?= $title ?> · MuPGA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700&family=Cinzel:wght@400;600;700&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/main.css<?= $v ?>">
</head>
<body>

<div class="landing-embers" aria-hidden="true">
<?php for ($i = 0; $i < 28; $i++):
    $x     = mt_rand(0, 1000) / 10;          // posición horizontal: 0% .. 100%
    $size  = mt_rand(20, 50) / 10;           // tamaño: 2px .. 5px
    $dur   = mt_rand(70, 150) / 10;          // duración del ciclo: 7s .. 15s
    $delay = -1 * (mt_rand(0, 150) / 10);    // negativo: arrancan a mitad de recorrido, no todas juntas
    $drift = mt_rand(-45, 45);               // deriva horizontal en px
    $magic = mt_rand(1, 100) <= 25;          // ~1 de cada 4, chispa "mágica" violeta en vez de dorada
?>
  <span class="ember<?= $magic ? ' ember--magic' : '' ?>" style="--x:<?= $x ?>%; --size:<?= $size ?>px; --dur:<?= $dur ?>s; --delay:<?= $delay ?>s; --drift:<?= $drift ?>px;"></span>
<?php endfor; ?>
</div>

<div class="page-wrapper page-wrapper--landing">

  <header class="site-header">
    <a href="<?= $base ?>/" class="site-logo">Mu<span>PGA</span></a>
    <nav class="site-nav" aria-label="Navegación principal">
      <a href="https://foro.mupga.com.ar" class="nav-link">Foro</a>
      <a href="https://wiki.mupga.com.ar" class="nav-link" target="_blank" rel="noopener">Wiki</a>
      <a href="https://discord.com/invite/xTxFHSmVhf" class="nav-link" target="_blank" rel="noopener">Discord</a>
    </nav>
  </header>

  <?= $content ?>

  <footer class="site-footer">
    <span class="footer-logo">MuPGA</span>
    <span>© <?= $year ?> MuPGA · Todos los derechos reservados.</span>
    <nav class="footer-links">
      <a href="https://discord.com/invite/xTxFHSmVhf" target="_blank" rel="noopener">Discord</a>
      <a href="https://chat.whatsapp.com/DqaUqom63aFALaBsK2l7of" target="_blank" rel="noopener">WhatsApp</a>
    </nav>
  </footer>

</div>

<script src="<?= $base ?>/assets/js/config.js<?= $v ?>"></script>
<script src="<?= $base ?>/assets/js/landing.js<?= $v ?>" defer></script>
</body>
</html>
