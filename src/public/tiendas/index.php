<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Radar de Tiendas';
$extraJs   = 'tiendas.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Radar de Tiendas</h1>
    <p class="page-hero-sub">Quién tiene la tienda personal abierta ahora mismo, y a qué precio
      publicó cada ítem. Se actualiza solo — para comprar hay que acercarte en el juego.</p>
  </div>

  <section class="section">
    <div id="tiendas-cross-link"></div>
    <div id="tiendas-radar-container">
      <div class="card-grid card-grid--3">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <div class="shop-card skeleton" style="height:150px;border-radius:var(--radius)"></div>
        <?php endfor; ?>
      </div>
    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
