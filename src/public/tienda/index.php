<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Tienda WCoin';
$extraJs   = 'tienda.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Tienda WCoin</h1>
    <p class="page-hero-sub">Canjeá tus WCoins por ítems del CashShop — se entregan igual que
      comprando con la tecla "X" en el juego.</p>
  </div>

  <section class="section">
    <div id="tienda-balance" class="account-card" style="margin-bottom:var(--gap-md)">
      <p class="state-message">Cargando saldo…</p>
    </div>
    <div id="tienda-container">
      <div class="card-grid card-grid--3">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <div class="tienda-card skeleton" style="height:180px;border-radius:var(--radius)"></div>
        <?php endfor; ?>
      </div>
    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
