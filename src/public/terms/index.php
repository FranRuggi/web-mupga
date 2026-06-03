<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Términos de Servicio';
ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Términos de Servicio</h1>
    <p class="page-hero-sub">Última actualización: 2026</p>
  </div>

  <section class="section">
    <div style="max-width:800px;margin:0 auto">

      <h2 style="color:var(--gold);margin-bottom:1rem">🇦🇷 Español</h2>

      <?php
      $termsES = [
        '1. Acuerdo Legal' => 'Al acceder a MUPGA, el usuario acepta estos Términos de Servicio y nuestra Política de Privacidad. Este acuerdo rige la conducta dentro y fuera del juego. MUPGA es una comunidad privada y se reserva el derecho de admisión y permanencia.',
        '2. Requisitos de Edad' => 'El sitio y el servicio están destinados a usuarios de 13 años o más. Si eres menor de edad, debés revisar este acuerdo con tus padres o tutores legales.',
        '3. Wcoins y Donaciones' => 'Los "Wcoins" son una moneda virtual utilizada exclusivamente dentro de MUPGA. La adquisición de Wcoins se considera una donación voluntaria destinada al mantenimiento del servidor. Los Wcoins no poseen valor monetario real ni son canjeables por dinero en efectivo.',
        '4. Política de No Reembolso' => 'Al ser bienes digitales de consumo inmediato, una vez que los Wcoins o beneficios VIP han sido acreditados en la cuenta, no se realizarán reembolsos ni devoluciones bajo ninguna circunstancia. Cualquier intento de cancelación o reversión de pago (chargeback) resultará en la suspensión permanente de la cuenta.',
        '5. Seguridad de la Cuenta' => 'El usuario es el único responsable de la seguridad de su cuenta y contraseña. El intercambio, venta o préstamo de cuentas está estrictamente prohibido. MUPGA nunca te pedirá tu contraseña por ningún medio.',
        '6. Uso de Hacks y Exploits' => 'El uso de software de terceros (Hacks, Bots, Cheats) o el abuso de bugs conocidos para obtener ventaja está totalmente prohibido y resultará en el bloqueo inmediato y permanente de la cuenta.',
        '7. Mantenimiento y Caídas' => 'MUPGA no se responsabiliza por la pérdida de ítems o tiempo de juego debido a mantenimientos programados, ataques externos o fallas técnicas ajenas a nuestra administración.',
        '8. Jurisdicción y Cierre' => 'Cualquier controversia será resuelta bajo las leyes de la República Argentina. Recordá que sos un invitado en nuestra comunidad; actuá con respeto.',
      ];
      foreach ($termsES as $title => $text): ?>
        <h3 style="color:var(--cyan);margin:1.5rem 0 .5rem"><?= htmlspecialchars($title) ?></h3>
        <p style="color:var(--text);line-height:1.8"><?= htmlspecialchars($text) ?></p>
      <?php endforeach; ?>

      <hr style="border:none;border-top:1px solid var(--border);margin:3rem 0">

      <h2 style="color:var(--gold);margin-bottom:1rem">🇺🇸 English</h2>

      <?php
      $termsEN = [
        '1. Legal Agreement' => 'By accessing MUPGA, you agree to these Terms of Service and our Privacy Policy. This agreement governs conduct in and out of the game. MUPGA is a private community and reserves the right to refuse service to anyone at any time.',
        '2. Age Requirements' => 'The site and service are intended for users age 13 and over. If you are a minor, you must review this agreement with your parents or legal guardians.',
        '3. Wcoins and Donations' => '"Wcoins" are a virtual currency used exclusively within MUPGA. Acquiring Wcoins is considered a voluntary donation for server maintenance. They have no real cash value and are not redeemable for cash.',
        '4. No Refund Policy' => 'As these are digital goods for immediate consumption, once Wcoins or VIP benefits are credited to your account, no refunds or returns will be issued. Any attempt to cancel or reverse a payment (chargeback) will result in a permanent account ban.',
        '5. Account Security' => 'The user is solely responsible for account and password security. Account sharing, selling, or trading is strictly prohibited. MUPGA will never ask for your password through any means.',
        '6. Hacks and Exploits' => 'The use of third-party software (Hacks, Bots, Cheats) or abusing known bugs is strictly prohibited and will result in an immediate and permanent account ban.',
        '7. Maintenance and Downtime' => 'MUPGA is not responsible for item loss or lost playtime due to scheduled maintenance, external attacks, or technical failures beyond our control.',
        '8. Jurisdiction' => 'Any disputes will be resolved under the laws of the Republic of Argentina. Remember you are a guest in our community; please act with respect.',
      ];
      foreach ($termsEN as $title => $text): ?>
        <h3 style="color:var(--cyan);margin:1.5rem 0 .5rem"><?= htmlspecialchars($title) ?></h3>
        <p style="color:var(--text);line-height:1.8"><?= htmlspecialchars($text) ?></p>
      <?php endforeach; ?>

    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
