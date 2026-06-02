<?php
/**
 * Layout base del sitio MuPGA.
 * Incluir al final de cada página después de definir $pageTitle y $content.
 *
 * Variables esperadas:
 *   $pageTitle (string) — título de la página
 *   $content   (string) — HTML del cuerpo de la página (generado con ob_start/ob_get_clean)
 */

$base    = rtrim($_ENV['APP_BASE_URL'] ?? '/', '/');
$title   = htmlspecialchars($pageTitle ?? 'MuPGA', ENT_QUOTES);
$year    = date('Y');
?>
<!DOCTYPE html>
<html lang="es" data-base-url="<?= $base ?>/">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="MuPGA — Servidor privado de MU Online Season 6. Unite a la batalla.">
  <meta name="theme-color" content="#0d0b14">
  <title><?= $title ?> · MuPGA</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700&family=Cinzel:wght@400;600;700&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= $base ?>/assets/css/main.css">
</head>
<body>

<div class="page-wrapper">

  <!-- ── Header ── -->
  <header class="site-header">
    <a href="<?= $base ?>/" class="site-logo">Mu<span>PGA</span></a>

    <button class="nav-toggle" aria-label="Abrir menú" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <nav class="site-nav" aria-label="Navegación principal">
      <a href="<?= $base ?>/"          class="nav-link">Inicio</a>
      <a href="<?= $base ?>/rankings/" class="nav-link">Rankings</a>
      <a href="<?= $base ?>/info/"     class="nav-link">Info del servidor</a>
      <a href="<?= $base ?>/login/"    class="nav-link">Login</a>
      <a href="<?= $base ?>/register/" class="nav-link nav-cta">Registrarme</a>
    </nav>
  </header>

  <!-- ── Contenido de la página ── -->
  <?= $content ?>

  <!-- ── Sidebar ── -->
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
        <div class="skeleton" style="height:0.8rem;margin-bottom:0.6rem"></div>
        <div class="skeleton" style="height:0.8rem;margin-bottom:0.6rem"></div>
        <div class="skeleton" style="height:0.8rem;margin-bottom:0.6rem"></div>
        <div class="skeleton" style="height:0.8rem;margin-bottom:0.6rem"></div>
        <div class="skeleton" style="height:0.8rem"></div>
      </div>
    </div>

    <div class="widget">
      <p class="widget-title">🏰 Próximo Castle Siege</p>
      <div id="cs-countdown">
        <div class="skeleton" style="height:64px;border-radius:8px"></div>
      </div>
    </div>

  </aside>

  <!-- ── Footer ── -->
  <footer class="site-footer">
    <span class="footer-logo">MuPGA</span>
    <span>© <?= $year ?> MuPGA · Todos los derechos reservados.</span>
    <nav class="footer-links" aria-label="Redes sociales">
      <a href="#" aria-label="Discord">Discord</a>
      <a href="#" aria-label="Facebook">Facebook</a>
      <a href="#" aria-label="Foro">Foro</a>
    </nav>
  </footer>

</div><!-- /.page-wrapper -->

<script src="<?= $base ?>/assets/js/app.js" defer></script>
</body>
</html>
