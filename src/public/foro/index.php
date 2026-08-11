<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Foro';
$extraJs   = 'foro.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Foro MuPGA</h1>
    <p class="page-hero-sub">Comunidad, guías, guilds y compra/venta — publicá con la misma cuenta del sitio.</p>
  </div>

  <section class="section">
    <div id="foro-categorias-container">
      <div class="card-grid card-grid--3">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <div class="forum-category-card skeleton" style="height:110px;border-radius:var(--radius)"></div>
        <?php endfor; ?>
      </div>
    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
