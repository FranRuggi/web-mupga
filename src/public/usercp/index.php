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
        <div class="account-info-row" id="info-vip-expire-row" style="display:none">
          <span class="account-info-row__label">VIP vence</span>
          <span class="account-info-row__value" id="info-vip-expire">—</span>
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
          <div class="skeleton" style="height:52px;margin-bottom:0.5rem;border-radius:8px"></div>
          <div class="skeleton" style="height:52px;border-radius:8px"></div>
        </div>
      </div>

    </div>

    <!-- ── Opciones de personaje ── -->
    <div class="account-card game-options-card" id="game-options-card">
      <p class="account-card__title">⚔ Opciones de personaje</p>
      <p class="game-options-desc">
        Seleccioná tu personaje y aplicá las acciones disponibles.
        Los cambios se aplican al servidor en tiempo real.
      </p>

      <div class="game-options-selector">
        <label class="form-label" for="char-select">Personaje</label>
        <select class="form-input" id="char-select">
          <option value="">Cargando personajes...</option>
        </select>
      </div>

      <div class="game-options-actions">

        <div class="game-option-btn-group">
          <button class="btn btn-secondary game-option-btn" id="btn-unstick" disabled>
            <span class="game-option-icon">📍</span>
            <span class="game-option-text">
              <strong>Unstick</strong>
              <small>Mover a Lorencia si quedaste trabado</small>
            </span>
          </button>
          <div id="msg-unstick" class="alert" role="alert"></div>
        </div>

        <div class="game-option-btn-group">
          <button class="btn btn-secondary game-option-btn" id="btn-clearpk" disabled>
            <span class="game-option-icon">🕊</span>
            <span class="game-option-text">
              <strong>Limpiar PK</strong>
              <small>Sacar el estado asesino del personaje</small>
            </span>
          </button>
          <div id="msg-clearpk" class="alert" role="alert"></div>
        </div>

        <div class="game-option-btn-group">
          <button class="btn btn-secondary game-option-btn" id="btn-resetstats" disabled>
            <span class="game-option-icon">⚡</span>
            <span class="game-option-text">
              <strong>Resetear Stats</strong>
              <small>Devolver puntos de estadísticas al pool</small>
            </span>
          </button>
          <div id="msg-resetstats" class="alert" role="alert"></div>
        </div>

        <div class="game-option-btn-group">
          <button class="btn btn-secondary game-option-btn" id="btn-resetml" disabled>
            <span class="game-option-icon">🌀</span>
            <span class="game-option-text">
              <strong>Resetear Árbol ML</strong>
              <small>Recuperar puntos del árbol maestro</small>
            </span>
          </button>
          <div id="msg-resetml" class="alert" role="alert"></div>
        </div>

        <div class="game-option-btn-group">
          <button class="btn btn-secondary game-option-btn game-option-btn--reset" id="btn-resetchar" disabled>
            <span class="game-option-icon">🔁</span>
            <span class="game-option-text">
              <strong>Reset de personaje</strong>
              <small>Nivel 400 requerido — volvés al nivel 1</small>
            </span>
          </button>
          <div id="msg-resetchar" class="alert" role="alert"></div>
        </div>

      </div>
    </div>

    <!-- ── Agregar puntos de stats ── -->
    <div class="account-card game-options-card" id="addstats-card">
      <p class="account-card__title">➕ Agregar puntos de estadística</p>
      <p class="game-options-desc">
        Personaje seleccionado: <strong id="addstats-char-name">—</strong> ·
        Puntos disponibles: <strong id="addstats-available">—</strong>
      </p>
      <div id="addstats-stats" class="current-stats-display" aria-label="Stats actuales"></div>
      <div id="msg-addstats" class="alert" role="alert"></div>
      <form id="form-addstats" class="addstats-form" novalidate>
        <div class="addstats-grid">
          <div class="addstats-row">
            <label class="addstats-label" for="add-str">Fuerza</label>
            <input class="form-input addstats-input" type="number" id="add-str" min="0" max="9999" value="0">
          </div>
          <div class="addstats-row">
            <label class="addstats-label" for="add-agi">Agilidad</label>
            <input class="form-input addstats-input" type="number" id="add-agi" min="0" max="9999" value="0">
          </div>
          <div class="addstats-row">
            <label class="addstats-label" for="add-vit">Vitalidad</label>
            <input class="form-input addstats-input" type="number" id="add-vit" min="0" max="9999" value="0">
          </div>
          <div class="addstats-row">
            <label class="addstats-label" for="add-ene">Energía</label>
            <input class="form-input addstats-input" type="number" id="add-ene" min="0" max="9999" value="0">
          </div>
          <div class="addstats-row" id="add-cmd-row" style="display:none">
            <label class="addstats-label" for="add-cmd">Liderazgo</label>
            <input class="form-input addstats-input" type="number" id="add-cmd" min="0" max="9999" value="0">
          </div>
        </div>
        <div class="addstats-total">
          Total a gastar: <strong id="addstats-total">0</strong> puntos
        </div>
        <button class="btn btn-primary" type="submit" id="btn-addstats">Agregar puntos</button>
      </form>
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
