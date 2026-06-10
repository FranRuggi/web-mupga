<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Tienda WCoin';
$extraJs   = 'donate.js';
ob_start();
?>

<main class="site-main">

  <div class="donate-hero">
    <h1 class="donate-hero-title">Tienda WCoin</h1>
    <p class="donate-hero-sub">
      Convertí tu moneda en WCoin y recargá tu cuenta al instante.
    </p>
  </div>

  <section class="section">

    <!-- Mensaje: tienda no disponible o error global -->
    <div id="store-status" class="donate-pending-notice" hidden></div>

    <!-- Exchange principal -->
    <div id="exchange-main" class="exchange-wrapper">

      <!-- Email -->
      <div class="exchange-card">
        <p class="exchange-label">Tu email</p>
        <div class="exchange-email-section">
          <input id="inp-email" type="email" class="exchange-email-input"
                 placeholder="nombre@mail.com" autocomplete="email">
          <p class="exchange-email-hint">
            Usamos este email para enviarte la confirmación y cualquier novedad sobre tu compra.
          </p>
        </div>
      </div>

      <!-- Card DE (moneda del juego, ej: WCoin) -->
      <div class="exchange-card">
        <p class="exchange-label">De</p>
        <div class="exchange-row">
          <!-- Custom picker con ícono -->
          <div class="currency-picker" id="picker-from">
            <button type="button" class="currency-picker__btn" id="btn-picker-from" disabled>
              <span id="picker-from-content" class="currency-picker__content">
                <span class="currency-picker__placeholder">Cargando…</span>
              </span>
              <svg class="currency-picker__chevron" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <div class="currency-picker__dropdown" id="dropdown-from" hidden></div>
          </div>
          <!-- Input oculto para mantener el value (el JS actual lo lee como $selFrom.value) -->
          <input type="hidden" id="sel-from" value="">
          <input id="inp-amount" type="number" class="exchange-input"
                 min="1" max="100000" step="1" placeholder="0" disabled>
        </div>
        <!-- Aviso de límite -->
        <div id="amount-limit-warn" class="amount-limit-warn" hidden>
          Máximo 100,000 WC por compra.
        </div>
      </div>

      <!-- Flecha separadora -->
      <div class="exchange-separator">
        <div class="exchange-arrow">&#8595;</div>
      </div>

      <!-- Card A (fiat / crypto, ej: ARS, USDT) -->
      <div class="exchange-card">
        <p class="exchange-label">A</p>
        <div class="exchange-row">
          <div class="currency-picker" id="picker-to">
            <button type="button" class="currency-picker__btn" id="btn-picker-to" disabled>
              <span id="picker-to-content" class="currency-picker__content">
                <span class="currency-picker__placeholder">Cargando…</span>
              </span>
              <svg class="currency-picker__chevron" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <div class="currency-picker__dropdown" id="dropdown-to" hidden></div>
          </div>
          <input type="hidden" id="sel-to" value="">
          <div id="quoted-amount" class="exchange-quote-display">—</div>
        </div>
      </div>

      <!-- Botón Calcular -->
      <div class="exchange-actions">
        <button id="btn-calculate" class="btn btn-secondary" disabled>Calcular</button>
      </div>

      <!-- Resultado de cotización (aparece tras calcular) -->
      <div id="quote-result" class="exchange-quote-result" hidden></div>

      <!-- Proveedores de pago (aparece después de cotizar) -->
      <div id="providers-section" class="exchange-providers-section" hidden>
        <p class="exchange-label">Medio de pago</p>
        <select id="sel-provider" class="exchange-select exchange-select--full">
          <option value="">Seleccioná un medio de pago...</option>
        </select>
        <div id="provider-warning" class="exchange-warning" hidden></div>
      </div>

      <!-- Botón Comprar -->
      <div class="exchange-actions">
        <button id="btn-buy" class="btn btn-primary" disabled>Comprar</button>
      </div>

      <!-- Error en la creación de la orden -->
      <div id="buy-error" class="exchange-error" hidden></div>

    </div><!-- /#exchange-main -->

  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
