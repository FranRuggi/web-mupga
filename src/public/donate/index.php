<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Recargar WCoin';
$extraJs   = 'donate.js';
ob_start();
?>

<main class="site-main">

  <div class="donate-hero">
    <h1 class="donate-hero-title">Recargar WCoin</h1>
    <p class="donate-hero-sub">
      Elegí el paquete que mejor se adapte a vos.<br>
      Serás redirigido a nuestra plataforma de pagos segura.
    </p>
  </div>

  <section class="section">

    <!--
      ============================================================
      AVISO DE SISTEMA DE PAGOS
      Cuando el sistema de pagos esté activo, este bloque
      desaparece automáticamente. La URL se configura en .env:
        DONATION_URL=https://pagos.mupga.com
      ============================================================
    -->
    <div id="donate-notice" class="donate-pending-notice" style="display:none"></div>

    <!-- donate.js carga los paquetes desde /api/donate.php -->
    <div class="donate-grid" id="donate-packages">
      <!-- Skeleton mientras carga -->
      <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="donate-card skeleton" style="height:260px"></div>
      <?php endfor; ?>
    </div>

    <p style="text-align:center;margin-top:var(--gap-lg);font-size:0.8rem;color:var(--text-dim)">
      Los créditos se acreditan automáticamente en tu cuenta una vez confirmado el pago.<br>
      Ante cualquier inconveniente, contactanos por Discord.
    </p>

  </section>

</main>

<script>
  // Mostrar bloque de aviso si es necesario (manejado por donate.js)
  document.getElementById('donate-notice').style.display = '';
</script>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
