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
        Elegiste <strong>transferencia bancaria</strong> como medio de pago. Para acreditar
        tus <strong>WCoin</strong> necesitamos que nos mandes el comprobante de la
        transferencia — no se acredita solo, un admin lo valida a mano.
      </p>

      <p class="payment-result-msg">
        Escribinos por WhatsApp o generá un reclamo de compra adjuntando el comprobante,
        lo que te resulte más cómodo.
      </p>

      <p class="payment-result-note" id="order-ref" hidden></p>

      <div class="payment-result-actions">
        <a href="https://chat.whatsapp.com/DqaUqom63aFALaBsK2l7of" target="_blank" rel="noopener" class="btn btn-primary">
          Escribir por WhatsApp
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
</script>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
