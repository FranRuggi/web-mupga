<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Iniciar sesión';
$extraJs   = 'login.js';
ob_start();
?>

<main class="site-main">
  <div class="form-page">
    <div class="form-card">

      <h1 class="form-title">MuPGA</h1>
      <p class="form-subtitle">Ingresá con tu cuenta del servidor</p>

      <div id="form-alert" class="alert" role="alert"></div>

      <form id="login-form" novalidate autocomplete="off">
        <div class="form-group">
          <label class="form-label" for="username">Usuario</label>
          <input class="form-input" type="text" id="username" name="username"
                 placeholder="Tu nombre de usuario" maxlength="10" autocomplete="username" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Contraseña</label>
          <input class="form-input" type="password" id="password" name="password"
                 placeholder="Tu contraseña" maxlength="10" autocomplete="current-password" required>
        </div>

        <div class="form-actions">
          <button class="btn btn-primary btn-full" type="submit" id="btn-login">Ingresar</button>
        </div>
      </form>

      <div class="form-link" style="margin-top:1.5rem">
        ¿No tenés cuenta? <a href="../register/">Registrarme</a>
      </div>

    </div>
  </div>
</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
