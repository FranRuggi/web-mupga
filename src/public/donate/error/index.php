<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
$pageTitle = 'Pago no procesado';
ob_start();
?>

<main class="site-main">
  <section class="section">

    <div class="payment-result payment-result--error">

      <div class="payment-result-icon">&#10007;</div>

      <h1 class="payment-result-title">Pago no procesado</h1>

      <p class="payment-result-msg">
        El pago no pudo completarse correctamente.<br>
        No se realizó ningún cobro, o el monto será reintegrado según la política del medio de pago.
      </p>

      <p class="payment-result-note">
        Para resolver el inconveniente, contactanos por
        <a href="https://discord.com/invite/xTxFHSmVhf" target="_blank" rel="noopener">Discord</a>
        o
        <a href="https://chat.whatsapp.com/DqaUqom63aFALaBsK2l7of" target="_blank" rel="noopener">WhatsApp</a>
        con los detalles de tu intento de pago.
      </p>

      <a id="cta-donate" href="#" class="btn btn-secondary">Volver a la tienda</a>

    </div>

  </section>
</main>

<script>
  var base = (document.documentElement.dataset.baseUrl || '').replace(/\/$/, '');
  document.getElementById('cta-donate').href = base + '/donate/';
</script>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
