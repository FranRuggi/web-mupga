<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Eventos';
$extraJs   = 'eventos.js';
ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Eventos</h1>
    <p class="page-hero-sub">Torneos y actividades del servidor — anotate y vas a la lista.</p>
  </div>

  <section class="section">

    <div id="eventos-alert" class="alert" role="alert"></div>

    <div id="eventos-list">
      <div class="prode-loading">
        <?php for ($i = 0; $i < 2; $i++): ?>
          <div class="skeleton" style="height:160px;border-radius:10px;margin-bottom:1rem"></div>
        <?php endfor; ?>
      </div>
    </div>

  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
