<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Crear cuenta';
$extraJs   = 'register.js';
ob_start();
?>

<main class="site-main">
  <div class="form-page">
    <div class="form-card">

      <h1 class="form-title">Registro</h1>
      <p class="form-subtitle">Creá tu cuenta para jugar en MuPGA</p>

      <div id="form-alert" class="alert" role="alert"></div>

      <form id="register-form" novalidate autocomplete="off">

        <div class="form-group">
          <label class="form-label" for="username">Usuario</label>
          <input class="form-input" type="text" id="username" name="username"
                 placeholder="4 a 10 caracteres" maxlength="10" autocomplete="off" required>
          <p class="form-hint">Solo letras y números. Este nombre se usa también en el juego.</p>
          <span class="field-error" id="err-username"></span>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Contraseña</label>
          <input class="form-input" type="password" id="password" name="password"
                 placeholder="8 a 10 caracteres" maxlength="10" autocomplete="new-password" required>
          <p class="form-hint">Usá la misma contraseña para entrar al cliente del juego.</p>
          <span class="field-error" id="err-password"></span>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirm">Confirmar contraseña</label>
          <input class="form-input" type="password" id="password_confirm" name="password_confirm"
                 placeholder="Repetí tu contraseña" maxlength="10" autocomplete="new-password" required>
          <span class="field-error" id="err-password_confirm"></span>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input class="form-input" type="email" id="email" name="email"
                 placeholder="tu@email.com" maxlength="50" autocomplete="email" required>
          <span class="field-error" id="err-email"></span>
        </div>

        <div class="form-actions">
          <button class="btn btn-primary btn-full" type="submit" id="btn-register">Crear cuenta</button>
        </div>
      </form>

      <div class="form-link" style="margin-top:1.5rem">
        ¿Ya tenés cuenta? <a href="../login/">Iniciar sesión</a>
      </div>

    </div>
  </div>
</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
