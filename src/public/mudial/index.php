<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Prode MuPGA';
$extraJs   = 'mudial.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Prode MuPGA</h1>
    <p class="page-hero-sub">Predecí los resultados y ganá WCoins y días VIP automáticamente.</p>
  </div>

  <section class="section">

    <div id="prode-alert" class="alert" role="alert"></div>

    <!-- Tabs: Partidos / Ranking -->
    <div class="tab-nav" id="prode-tabs">
      <button class="tab-btn active" data-tab="matches">Partidos</button>
      <button class="tab-btn"        data-tab="ranking">Ranking</button>
    </div>

    <!-- Panel Partidos -->
    <div id="panel-matches">
      <div id="matches-container">
        <div class="prode-loading">
          <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="prode-match-card skeleton" style="height:110px;border-radius:10px;margin-bottom:0.75rem"></div>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    <!-- Panel Ranking (oculto hasta click) -->
    <div id="panel-ranking" hidden>
      <div id="ranking-container">
        <div class="prode-loading"><?= implode('', array_map(fn() => '<div class="skeleton" style="height:52px;border-radius:8px;margin-bottom:0.5rem"></div>', range(1,8))) ?></div>
      </div>
    </div>

  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
