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

$hasQrPopup = false;
foreach ($rates as $r) {
    if (!empty($r['qr_popup'])) { $hasQrPopup = true; break; }
}

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

    <?php if ($rates): ?>
    <h2 class="donate2-section-title">Medios de pago</h2>
    <div class="donate2-grid">
      <?php foreach ($rates as $r):
        $hasAction = !empty($r['action_url']) || !empty($r['qr_popup']);
      ?>
      <div class="donate2-card">
        <div class="donate2-card-icon"><?= htmlspecialchars($r['icon'] ?? '💰') ?></div>
        <div class="donate2-card-provider"><?= htmlspecialchars($r['provider'] ?? '') ?></div>
        <div class="donate2-card-rate"><?= htmlspecialchars($r['rate'] ?? '') ?></div>
        <?php if (!empty($r['notes'])): ?>
        <div class="donate2-card-notes"><?= htmlspecialchars($r['notes']) ?></div>
        <?php endif; ?>

        <?php if (!empty($r['action_url'])): ?>
        <div class="donate2-card-action">
          <a href="<?= htmlspecialchars($r['action_url']) ?>"
             class="donate2-card-btn donate2-card-btn--mp"
             target="_blank" rel="noopener noreferrer">
            <?= htmlspecialchars($r['action_label'] ?? 'Pagar ahora') ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          </a>
        </div>
        <?php elseif (!empty($r['qr_popup'])): ?>
        <div class="donate2-card-action">
          <button type="button" class="donate2-card-btn donate2-card-btn--qr" data-open-qr>
            Ver QR de pago
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h3v-3h-3v3"/></svg>
          </button>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($howItWorks): ?>
    <div class="donate2-how">
      <h2 class="donate2-section-title">¿Cómo funciona?</h2>
      <p class="donate2-how-text"><?= htmlspecialchars($howItWorks) ?></p>
    </div>
    <?php endif; ?>

    <div class="donate2-cta">
      <a href="<?= htmlspecialchars($contactUrl) ?>"
         class="btn btn-primary donate2-cta-btn"
         target="_blank" rel="noopener noreferrer">
        Contactar por Discord
      </a>
    </div>

  </section>

</main>

<?php if ($hasQrPopup): ?>
<div id="binance-modal" class="donate2-modal" hidden aria-modal="true" role="dialog" aria-label="QR Binance Pay">
  <div id="binance-modal-overlay" class="donate2-modal__overlay"></div>
  <div class="donate2-modal__inner">
    <p class="donate2-modal__title">Binance Pay</p>
    <p class="donate2-modal__sub">Escaneá el código QR con tu app de Binance</p>
    <img src="<?= $base ?>/assets/img/binance.jpeg"
         alt="QR Binance Pay MuPGA"
         class="donate2-modal__img">
    <div class="donate2-modal__rate">1 USDT = 1.000 WCoins</div>
    <button id="binance-modal-close" type="button" class="donate2-modal__close">
      Cerrar
    </button>
  </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
