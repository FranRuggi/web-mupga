<?php
/**
 * Layout base del sitio MuPGA.
 * Variables esperadas: $pageTitle (string), $content (string), $extraJs (string|null)
 */

// Base URL robusto: compara DOCUMENT_ROOT con la ubicación real de src/public/
// Funciona en cualquier subdirectorio (rankings/, info/, etc.) sin depender de APP_BASE_URL.
$docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
$pubDir  = rtrim(str_replace('\\', '/', realpath(SRC_ROOT . '/public')), '/');
$webPath = str_replace($docRoot, '', $pubDir); // '' para raíz, '/mupga' para subdir
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base    = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $webPath;

$title   = htmlspecialchars($pageTitle ?? 'MuPGA', ENT_QUOTES);
$year    = date('Y');
?>
<!DOCTYPE html>
<html lang="es" data-base-url="<?= $base ?>/">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="MuPGA — Servidor privado de MU Online Season 6.">
  <meta name="theme-color" content="#0d0b14">
  <title><?= $title ?> · MuPGA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700&family=Cinzel:wght@400;600;700&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
<?php $v = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? '?v=' . time() : '?v=1'; ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/main.css<?= $v ?>">
</head>
<body>

<div class="page-wrapper">

  <header class="site-header">
    <a href="<?= $base ?>/" class="site-logo">Mu<span>PGA</span></a>
    <button class="nav-toggle" aria-label="Abrir menú" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <nav class="site-nav" aria-label="Navegación principal">
      <a href="<?= $base ?>/"            class="nav-link">Inicio</a>
      <a href="<?= $base ?>/news/"      class="nav-link">Noticias</a>
      <a href="<?= $base ?>/rankings/"  class="nav-link">Rankings</a>
      <a href="<?= $base ?>/info/"      class="nav-link">Info</a>
      <a href="<?= $base ?>/downloads/" class="nav-link">Descargas</a>
      <a href="<?= $base ?>/donate/"    class="nav-link">WCoin</a>
      <!-- Links para usuarios NO autenticados (JS los oculta si hay sesión) -->
      <a href="<?= $base ?>/login/"     class="nav-link"     data-guest-show>Login</a>
      <a href="<?= $base ?>/register/"  class="nav-link nav-cta" data-guest-show>Registrarme</a>
      <!-- Links para usuarios autenticados (JS los muestra si hay sesión) -->
      <a href="<?= $base ?>/usercp/"    class="nav-link"     data-auth-show hidden>Mi cuenta</a>
      <a href="#" id="nav-logout"       class="nav-link"     data-auth-show hidden>Salir</a>
    </nav>
  </header>

  <?= $content ?>

  <aside class="site-sidebar" aria-label="Panel lateral">

    <div class="widget">
      <p class="widget-title"><span class="online-dot"></span>Jugadores online</p>
      <div class="online-count">
        <div class="online-number" id="online-count">—</div>
        <div class="online-label">conectados ahora</div>
      </div>
    </div>

    <div class="widget">
      <p class="widget-title">Servidor</p>
      <div id="server-stats">
        <?php for ($i = 0; $i < 5; $i++): ?>
          <div class="skeleton" style="height:0.8rem;margin-bottom:<?= $i < 4 ? '0.6rem' : '0' ?>"></div>
        <?php endfor; ?>
      </div>
    </div>

  </aside>

  <footer class="site-footer">
    <span class="footer-logo">MuPGA</span>
    <span>© <?= $year ?> MuPGA · Todos los derechos reservados.</span>
    <nav class="footer-links">
      <a href="#">Discord</a>
      <a href="#">Facebook</a>
      <a href="#">Foro</a>
    </nav>
  </footer>

</div>

<?php if (!empty($_ENV['TURNSTILE_SITE_KEY'])): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<script src="<?= $base ?>/assets/js/config.js<?= $v ?>"></script>
<script src="<?= $base ?>/assets/js/app.js<?= $v ?>" defer></script>
<script src="<?= $base ?>/assets/js/auth.js<?= $v ?>" defer></script>
<?php if (!empty($extraJs)): ?>
<script src="<?= $base ?>/assets/js/<?= htmlspecialchars($extraJs) ?><?= $v ?>" defer></script>
<?php endif; ?>
</body>
</html>
