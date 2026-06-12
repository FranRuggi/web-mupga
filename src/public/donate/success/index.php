<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
$pageTitle = 'Pago procesado';
ob_start();
?>

<main class="site-main">
  <section class="section">

    <div class="payment-result payment-result--success">

      <div class="payment-result-icon">&#10003;</div>

      <h1 class="payment-result-title">¡Pago procesado!</h1>

      <p class="payment-result-msg">
        Tu pago fue recibido correctamente.<br>
        Los <strong>WCoin</strong> serán acreditados en tu cuenta
        en los próximos <strong>minutos</strong>, una vez que se confirme la transacción.
      </p>

      <p class="payment-result-note">
        Si pasados 30 minutos no ves el saldo reflejado en tu cuenta,
        contactanos por
        <a href="https://discord.com/invite/xTxFHSmVhf" target="_blank" rel="noopener">Discord</a>
        indicando el número de tu transacción.
      </p>

      <a id="cta-account" href="#" class="btn btn-primary">Ver mi cuenta</a>

    </div>

  </section>
</main>

<script>
  document.getElementById('cta-account').href =
    (document.documentElement.dataset.baseUrl || '').replace(/\/$/, '') + '/usercp/';
</script>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
