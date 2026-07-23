<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Donaciones y WCoins';
$extraJs   = 'donate2.js';

$dataFile = PROJECT_ROOT . '/data/donate.json';
$data     = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?? []) : [];

$description  = $data['description']  ?? '';
$rates        = $data['rates']        ?? [];
$howItWorks   = $data['how_it_works'] ?? '';
$bigAmountNote = $data['big_amount_note'] ?? '';
$contactUrl   = $data['contact_url']  ?? '#';

$hasQrPopup = false;
foreach ($rates as $r) {
    if (!empty($r['qr_popup'])) { $hasQrPopup = true; break; }
}

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Donaciones y WCoins</h1>
    <p class="page-hero-sub">Doná para ayudar a mantener el servidor activo y recibí WCoins para el Cash Shop.</p>
  </div>

  <section class="section">

    <?php if ($description): ?>
    <p class="donate2-description"><?= htmlspecialchars($description) ?></p>
    <?php endif; ?>

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

        <?php if (!empty($r['promos'])): ?>
        <div class="donate2-promo" data-promo>
          <select class="exchange-select exchange-select--full donate2-promo-select" aria-label="Elegí un monto de donación">
            <?php foreach ($r['promos'] as $p): ?>
            <option value="<?= htmlspecialchars($p['url'] ?? '#') ?>"><?= htmlspecialchars($p['label'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
          <div class="donate2-card-action">
            <a href="<?= htmlspecialchars($r['promos'][0]['url'] ?? '#') ?>"
               class="donate2-card-btn donate2-card-btn--mp"
               target="_blank" rel="noopener noreferrer" data-promo-link>
              <?= htmlspecialchars($r['action_label'] ?? 'Donar ahora') ?>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
          </div>
        </div>
        <?php elseif (!empty($r['action_url'])): ?>
        <div class="donate2-card-action">
          <a href="<?= htmlspecialchars($r['action_url']) ?>"
             class="donate2-card-btn donate2-card-btn--mp"
             target="_blank" rel="noopener noreferrer">
            <?= htmlspecialchars($r['action_label'] ?? 'Donar ahora') ?>
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
        <?php elseif (!empty($r['contact_only'])): ?>
        <div class="donate2-card-action donate2-card-action--split">
          <?php if (!empty($r['whatsapp_url'])): ?>
          <a href="<?= htmlspecialchars($r['whatsapp_url']) ?>"
             class="donate2-card-btn donate2-card-btn--wsp"
             target="_blank" rel="noopener noreferrer">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.28-.1-.48-.15-.68.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.68-1.63-.93-2.23-.24-.58-.49-.5-.68-.51-.17 0-.37-.01-.57-.01-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.87 1.22 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.27.49 1.7.63.72.23 1.37.2 1.89.12.58-.09 1.76-.72 2.01-1.42.25-.7.25-1.3.17-1.42-.07-.13-.28-.2-.57-.35Zm-5.43 7.43h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.38A9.86 9.86 0 0 1 2.1 12c0-5.46 4.44-9.9 9.91-9.9a9.86 9.86 0 0 1 7.01 2.9 9.86 9.86 0 0 1 2.9 7c-.01 5.46-4.45 9.91-9.88 9.91ZM12.04 0C5.37 0 0 5.37 0 12c0 2.11.55 4.18 1.6 6.01L0 24l6.14-1.61A12 12 0 0 0 12.04 24C18.7 24 24 18.63 24 12S18.7 0 12.04 0Z"/></svg>
            WhatsApp
          </a>
          <?php endif; ?>
          <?php if (!empty($r['ticket_url'])): ?>
          <a href="<?= htmlspecialchars($r['ticket_url']) ?>" class="donate2-card-btn donate2-card-btn--qr">
            Abrir un ticket
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($bigAmountNote): ?>
    <p class="donate2-note"><?= htmlspecialchars($bigAmountNote) ?></p>
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
    <img id="binance-qr-img" src=""
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
