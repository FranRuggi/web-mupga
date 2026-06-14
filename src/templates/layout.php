<?php
/**
 * Layout base del sitio MuPGA.
 * Variables esperadas: $pageTitle (string), $content (string), $extraJs (string|null)
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

$title   = htmlspecialchars($pageTitle ?? 'MuPGA', ENT_QUOTES);
$year    = date('Y');
?>
<!DOCTYPE html>
<?php
$paymentsApiUrl = rtrim($_ENV['PAYMENTS_API_URL'] ?? '', '/');
?>
<html lang="es" data-base-url="<?= $base ?>/" data-payments-url="<?= htmlspecialchars($paymentsApiUrl, ENT_QUOTES) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="MuPGA — Servidor privado de MU Online Season 6.">
  <meta name="theme-color" content="#0d0b14">
  <link rel="icon" type="image/png" href="<?= $base ?>/assets/img/logoweb.png">
  <link rel="apple-touch-icon" href="<?= $base ?>/assets/img/logoweb.png">
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
      <a href="<?= $base ?>/donate2/"   class="nav-link">WCoin</a>
      <a href="https://wiki.mupga.com.ar" class="nav-link" target="_blank" rel="noopener">Wiki</a>
      <a href="<?= $base ?>/mudial/"   class="nav-link">Prode</a>
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
    <span>© <?= $year ?> MuPGA · Todos los derechos reservados. · <a href="<?= $base ?>/privacy/" style="color:inherit;text-decoration:underline">Privacidad</a> · <a href="<?= $base ?>/terms/" style="color:inherit;text-decoration:underline">Términos</a></span>
    <nav class="footer-links">
      <a href="https://discord.com/invite/xTxFHSmVhf" target="_blank" rel="noopener">Discord</a>
      <a href="https://chat.whatsapp.com/DqaUqom63aFALaBsK2l7of" target="_blank" rel="noopener">WhatsApp</a>
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
