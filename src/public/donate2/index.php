<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Compra de WCoins';
$extraJs   = 'donate2.js';

$dataFile = PROJECT_ROOT . '/data/donate.json';
$data     = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?? []) : [];

$description  = $data['description']  ?? '';
$rates        = $data['rates']        ?? [];
$howItWorks   = $data['how_it_works'] ?? '';
$contactUrl   = $data['contact_url']  ?? '#';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Compra de WCoins</h1>
    <p class="page-hero-sub">Recargá tu cuenta y accedé a los beneficios del Cash Shop.</p>
  </div>

  <section class="section">

    <?php if ($description): ?>
    <p class="donate2-description"><?= htmlspecialchars($description) ?></p>
    <?php endif; ?>

    <!-- Cards de proveedores -->
    <?php if ($rates): ?>
    <h2 class="donate2-section-title">Medios de pago</h2>
    <div class="donate2-grid">
      <?php foreach ($rates as $r): ?>
      <div class="donate2-card">
        <div class="donate2-card-icon"><?= htmlspecialchars($r['icon'] ?? '💰') ?></div>
        <div class="donate2-card-provider"><?= htmlspecialchars($r['provider'] ?? '') ?></div>
        <div class="donate2-card-rate"><?= htmlspecialchars($r['rate'] ?? '') ?></div>
        <?php if (!empty($r['notes'])): ?>
        <div class="donate2-card-notes"><?= htmlspecialchars($r['notes']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Cómo funciona -->
    <?php if ($howItWorks): ?>
    <div class="donate2-how">
      <h2 class="donate2-section-title">¿Cómo funciona?</h2>
      <p class="donate2-how-text"><?= htmlspecialchars($howItWorks) ?></p>
    </div>
    <?php endif; ?>

    <!-- CTA -->
    <div class="donate2-cta">
      <a href="<?= htmlspecialchars($contactUrl) ?>"
         class="btn btn-primary donate2-cta-btn"
         target="_blank" rel="noopener noreferrer">
        Contactar por Discord
      </a>
    </div>

  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
