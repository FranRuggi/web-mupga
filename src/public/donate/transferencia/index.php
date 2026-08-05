<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
$pageTitle = 'Transferencia bancaria';
ob_start();
?>

<main class="site-main">
  <section class="section">

    <div class="payment-result payment-result--pending">

      <div class="payment-result-icon">🏦</div>

      <h1 class="payment-result-title">¡Ya casi! Completá tu pago por transferencia</h1>

      <p class="payment-result-msg">
        Elegiste <strong>transferencia bancaria</strong> como medio de pago. Transferí el
        monto de tu compra al alias de abajo y despues mandanos el comprobante — no se
        acredita solo, un admin lo valida a mano.
      </p>

      <div class="transfer-alias-box">
        <p class="transfer-alias-label">Alias para transferir</p>
        <div class="transfer-alias-value">
          <span id="alias-text">MUPGA.MP</span>
          <button type="button" id="btn-copy-alias" class="transfer-alias-copy">Copiar</button>
        </div>
        <p class="transfer-alias-hint" id="copy-feedback" hidden>Alias copiado.</p>
      </div>

      <p class="payment-result-msg">
        Mandanos el comprobante por WhatsApp, Discord o generando un reclamo de compra —
        lo que te resulte más cómodo.
      </p>

      <p class="payment-result-note" id="order-ref" hidden></p>

      <div class="payment-result-actions">
        <a href="https://chat.whatsapp.com/DqaUqom63aFALaBsK2l7of" target="_blank" rel="noopener" class="btn btn-primary">
          Escribir por WhatsApp
        </a>
        <a href="https://discord.com/invite/xTxFHSmVhf" target="_blank" rel="noopener" class="btn btn-secondary">
          Escribir por Discord
        </a>
        <a id="cta-reclamo" href="#" class="btn btn-secondary">
          Generar reclamo de compra
        </a>
      </div>

    </div>

  </section>
</main>

<script>
  var base   = (document.documentElement.dataset.baseUrl || '').replace(/\/$/, '');
  var params = new URLSearchParams(window.location.search);
  var orderId = params.get('orderId') || params.get('OrderId') || params.get('id') || '';

  var mensaje = 'Hola, hice una compra de WCoins por transferencia bancaria' +
    (orderId ? ' (orden ' + orderId + ')' : '') +
    ' y quiero mandar el comprobante.';

  document.getElementById('cta-reclamo').href =
    base + '/reclamos/?mensaje=' + encodeURIComponent(mensaje);

  if (orderId) {
    var ref = document.getElementById('order-ref');
    ref.textContent = 'Número de orden: ' + orderId;
    ref.hidden = false;
  }

  document.getElementById('btn-copy-alias').addEventListener('click', function () {
    var alias = document.getElementById('alias-text').textContent;
    var feedback = document.getElementById('copy-feedback');
    navigator.clipboard.writeText(alias).then(function () {
      feedback.hidden = false;
      setTimeout(function () { feedback.hidden = true; }, 2000);
    });
  });
</script>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
