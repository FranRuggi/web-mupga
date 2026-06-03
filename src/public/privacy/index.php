<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Política de Privacidad';
ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Política de Privacidad</h1>
    <p class="page-hero-sub">Última actualización: 2026</p>
  </div>

  <section class="section">
    <div style="max-width:800px;margin:0 auto">

      <h2 style="color:var(--gold);margin-bottom:1rem">🇦🇷 Español</h2>

      <h3 style="color:var(--cyan);margin:1.5rem 0 .5rem">Información que Recopilamos</h3>
      <ul style="color:var(--text);line-height:1.8;padding-left:1.5rem">
        <li><strong>Datos de Registro:</strong> Nombre de usuario (ID) y correo electrónico para la gestión de la cuenta.</li>
        <li><strong>Datos de Conexión:</strong> Dirección IP para auditorías de seguridad y prevención de ataques.</li>
        <li><strong>Transacciones:</strong> Registros de donaciones. No almacenamos datos de tarjetas; los pagos se procesan de forma externa y segura vía Mercado Pago, Binance o Payoneer.</li>
      </ul>

      <h3 style="color:var(--cyan);margin:1.5rem 0 .5rem">Uso de los Datos</h3>
      <p style="color:var(--text);line-height:1.8">
        Tus datos se utilizan exclusivamente para la operación técnica del juego, soporte al usuario y notificaciones críticas. Bajo ninguna circunstancia vendemos ni compartimos tu información con terceros para fines comerciales.
      </p>

      <h3 style="color:var(--cyan);margin:1.5rem 0 .5rem">Derechos y Seguridad</h3>
      <p style="color:var(--text);line-height:1.8">
        Tenés derecho a solicitar la rectificación o eliminación de tus datos. Implementamos medidas técnicas para proteger tu cuenta, pero el uso de una contraseña segura es responsabilidad del usuario.
      </p>

      <hr style="border:none;border-top:1px solid var(--border);margin:3rem 0">

      <h2 style="color:var(--gold);margin-bottom:1rem">🇺🇸 English</h2>

      <h3 style="color:var(--cyan);margin:1.5rem 0 .5rem">Information We Collect</h3>
      <ul style="color:var(--text);line-height:1.8;padding-left:1.5rem">
        <li><strong>Registration Data:</strong> Username (ID) and email address for account management.</li>
        <li><strong>Connection Data:</strong> IP address for security audits and fraud prevention.</li>
        <li><strong>Transactions:</strong> Donation logs. We do not store card information; payments are processed securely through external platforms like Mercado Pago, Binance, or Payoneer.</li>
      </ul>

      <h3 style="color:var(--cyan);margin:1.5rem 0 .5rem">Data Usage</h3>
      <p style="color:var(--text);line-height:1.8">
        Your data is used exclusively for technical operation, user support, and critical notifications. Under no circumstances do we sell or share your information with third parties for commercial purposes.
      </p>

      <h3 style="color:var(--cyan);margin:1.5rem 0 .5rem">User Rights and Security</h3>
      <p style="color:var(--text);line-height:1.8">
        You have the right to request access to or deletion of your data. We implement technical measures to protect your account, but using a secure password remains your responsibility.
      </p>

    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
