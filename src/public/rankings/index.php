<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Rankings';
$extraJs   = 'rankings.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Rankings</h1>
    <p class="page-hero-sub">Los mejores jugadores y guilds del servidor.</p>
  </div>

  <section class="section">
    <div class="rankings-header">
      <div id="tab-nav" class="tab-nav"></div>
      <p class="refresh-info">Actualización automática cada 2 min · Última: <span id="refresh-ts">—</span></p>
    </div>
    <div id="rankings-container"></div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
