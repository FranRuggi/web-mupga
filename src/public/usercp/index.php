<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Mi cuenta';
$extraJs   = 'usercp.js';
ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Mi cuenta</h1>
    <p class="page-hero-sub">Bienvenido, <span id="usercp-username">...</span></p>
  </div>

  <div class="usercp-layout">

    <!-- ── Fila 1: Info de cuenta + Personajes ── -->
    <div class="usercp-grid">

      <div class="account-card">
        <p class="account-card__title">Información de la cuenta</p>
        <div class="account-info-row">
          <span class="account-info-row__label">Usuario</span>
          <span class="account-info-row__value" id="info-username">—</span>
        </div>
        <div class="account-info-row">
          <span class="account-info-row__label">Email</span>
          <span class="account-info-row__value" id="info-email">—</span>
        </div>
        <div class="account-info-row">
          <span class="account-info-row__label">Estado VIP</span>
          <span class="account-info-row__value" id="info-vip">—</span>
        </div>
        <div class="account-info-row">
          <span class="account-info-row__label">Estado</span>
          <span class="account-info-row__value" id="info-online">—</span>
        </div>
        <div class="account-info-row">
          <span class="account-info-row__label">Cuenta creada</span>
          <span class="account-info-row__value" id="info-created">—</span>
        </div>
      </div>

      <div class="account-card">
        <p class="account-card__title">Mis personajes</p>
        <div class="char-list" id="char-list">
          <!-- usercp.js renderiza acá -->
          <div class="skeleton" style="height:52px;margin-bottom:0.5rem;border-radius:8px"></div>
          <div class="skeleton" style="height:52px;border-radius:8px"></div>
        </div>
      </div>

    </div>

    <!-- ── Fila 2: Saldo WCoin ── -->
    <div class="account-card">
      <p class="account-card__title">Saldo de créditos</p>
      <div class="balance-grid">
        <div class="balance-item">
          <span class="balance-amount" id="balance-wcoinc">—</span>
          <span class="balance-label">WCoin</span>
        </div>
        <div class="balance-item">
          <span class="balance-amount" id="balance-wcoinp">—</span>
          <span class="balance-label">WCoinP</span>
        </div>
        <div class="balance-item">
          <span class="balance-amount" id="balance-goblin">—</span>
          <span class="balance-label">Goblin Pts</span>
        </div>
      </div>
      <div style="text-align:center;margin-top:1rem">
        <a href="../donate/" class="btn btn-secondary">Recargar WCoin</a>
      </div>
    </div>

    <!-- ── Fila 3: Configuración ── -->
    <div class="usercp-grid">

      <div class="account-card">
        <p class="account-card__title">Cambiar contraseña</p>
        <div id="msg-password" class="alert" role="alert"></div>
        <form id="form-password" class="settings-form" novalidate>
          <div class="form-group">
            <label class="form-label" for="current_password">Contraseña actual</label>
            <input class="form-input" type="password" id="current_password" maxlength="10" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="new_password">Nueva contraseña</label>
            <input class="form-input" type="password" id="new_password" maxlength="10"
                   placeholder="8 a 10 caracteres" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirmar nueva contraseña</label>
            <input class="form-input" type="password" id="confirm_password" maxlength="10" required>
          </div>
          <button class="btn btn-primary" type="submit">Cambiar contraseña</button>
        </form>
      </div>

      <div class="account-card">
        <p class="account-card__title">Cambiar email</p>
        <div id="msg-email" class="alert" role="alert"></div>
        <form id="form-email" class="settings-form" novalidate>
          <div class="form-group">
            <label class="form-label" for="new_email">Nuevo email</label>
            <input class="form-input" type="email" id="new_email" maxlength="50"
                   placeholder="nuevo@email.com" required>
          </div>
          <button class="btn btn-primary" type="submit">Cambiar email</button>
        </form>
      </div>

    </div>

  </div><!-- /.usercp-layout -->
</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
