<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Descargas';
$extraJs   = 'downloads.js';
ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Descargas</h1>
    <p class="page-hero-sub">Descargá el cliente del juego y empezá a jugar en MuPGA.</p>
  </div>

  <section class="section">
    <div id="downloads-content">
      <!-- downloads.js carga api/downloadsdata.php y renderiza acá -->
      <div class="downloads-grid">
        <div class="download-card skeleton-block">
          <div class="skeleton" style="height:120px;border-radius:var(--radius)"></div>
        </div>
        <div class="download-card skeleton-block">
          <div class="skeleton" style="height:120px;border-radius:var(--radius)"></div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
