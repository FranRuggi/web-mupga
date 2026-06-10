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

      <!-- Card DE (moneda del juego) -->
      <div class="exchange-card">
        <p class="exchange-label">De</p>
        <div class="exchange-row">
          <select id="sel-from" class="exchange-select" disabled>
            <option value="">Cargando...</option>
          </select>
          <input id="inp-amount" type="number" class="exchange-input"
                 min="1" step="1" placeholder="Cantidad" disabled>
        </div>
      </div>

      <!-- Flecha separadora -->
      <div class="exchange-separator">
        <div class="exchange-arrow">&#8595;</div>
      </div>

      <!-- Card A (moneda fiat / crypto) -->
      <div class="exchange-card">
        <p class="exchange-label">A</p>
        <div class="exchange-row">
          <select id="sel-to" class="exchange-select" disabled>
            <option value="">Cargando...</option>
          </select>
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
