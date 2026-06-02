/* ============================================================
   MuPGA — usercp.js
   Panel de cuenta: carga perfil, personajes, balance.
   Depende de: auth.js, app.js (BASE, API, esc, avatarSrc, className)
   ============================================================ */

document.addEventListener('DOMContentLoaded', async () => {
  // Proteger la página — redirigir si no hay sesión
  if (!isAuthenticated()) {
    window.location.replace(`${BASE}/login/`);
    return;
  }

  const user = getUser();
  if (user) {
    const el = document.getElementById('usercp-username');
    if (el) el.textContent = user.username;
  }

  // Cargar datos en paralelo
  await Promise.all([
    loadProfile(),
    loadBalance(),
  ]);

  // Formularios de configuración
  initChangePassword();
  initChangeEmail();
});

// ── Perfil + personajes ───────────────────────────────────────

async function loadProfile() {
  const res = await authFetch('account/profile.php');
  if (!res) return;

  const data = await res.json();
  if (!res.ok) return;

  // Info de cuenta
  renderAccountInfo(data);

  // Personajes
  renderCharacters(data.characters ?? []);
}

function renderAccountInfo(data) {
  const fill = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  fill('info-username', data.username);
  fill('info-email',    data.email);

  const vipEl = document.getElementById('info-vip');
  if (vipEl) {
    vipEl.innerHTML = data.is_vip
      ? `<span class="badge badge--vip">⭐ VIP Nivel ${data.account_level}</span>`
      : `<span class="badge badge--normal">Normal</span>`;
  }

  fill('info-created', data.created_at
    ? new Date(data.created_at).toLocaleDateString('es-AR')
    : '—');

  const onlineEl = document.getElementById('info-online');
  if (onlineEl) {
    onlineEl.innerHTML = data.is_online
      ? '<span style="color:var(--cyan)">● Conectado</span>'
      : '<span style="color:var(--text-dim)">○ Desconectado</span>';
  }
}

function renderCharacters(chars) {
  const el = document.getElementById('char-list');
  if (!el) return;

  if (!chars.length) {
    el.innerHTML = '<p class="state-message" style="padding:1rem 0">No tenés personajes creados.</p>';
    return;
  }

  el.innerHTML = chars.map(c => `
    <div class="char-item animate-in">
      <img class="char-avatar"
           src="${avatarSrc(c.class)}"
           alt="${esc(className(c.class))}"
           onerror="this.src='${BASE}/assets/img/class/avatar.jpg'"
           loading="lazy">
      <div>
        <div class="char-name">${esc(c.name)}</div>
        <div class="char-class">${esc(className(c.class))}</div>
      </div>
      <div class="char-stats">
        Nv <span>${c.level}</span> · <span>${c.resets}</span> RST
      </div>
    </div>
  `).join('');
}

// ── Balance WCoin ─────────────────────────────────────────────

async function loadBalance() {
  const res = await authFetch('account/balance.php');
  if (!res) return;

  const data = await res.json();
  if (!res.ok) return;

  const fill = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val.toLocaleString('es-AR'); };
  fill('balance-wcoinc',  data.WCoinC      ?? 0);
  fill('balance-wcoinp',  data.WCoinP      ?? 0);
  fill('balance-goblin',  data.GoblinPoint ?? 0);
}

// ── Cambiar contraseña ────────────────────────────────────────

function initChangePassword() {
  const form = document.getElementById('form-password');
  form?.addEventListener('submit', async e => {
    e.preventDefault();
    const btn  = form.querySelector('[type=submit]');
    const body = {
      current_password:  document.getElementById('current_password').value,
      new_password:      document.getElementById('new_password').value,
      confirm_password:  document.getElementById('confirm_password').value,
    };

    setLoading(btn, true, 'Guardando...');
    const res  = await authFetch('account/changepassword.php', { method:'POST', body: JSON.stringify(body) });
    setLoading(btn, false, 'Cambiar contraseña');
    if (!res) return;

    const data = await res.json();
    showFormMsg('msg-password', data.message ?? data.error, res.ok ? 'success' : 'error');
    if (res.ok) form.reset();
  });
}

// ── Cambiar email ─────────────────────────────────────────────

function initChangeEmail() {
  const form = document.getElementById('form-email');
  form?.addEventListener('submit', async e => {
    e.preventDefault();
    const btn  = form.querySelector('[type=submit]');
    const body = { email: document.getElementById('new_email').value.trim() };

    setLoading(btn, true, 'Guardando...');
    const res  = await authFetch('account/changeemail.php', { method:'POST', body: JSON.stringify(body) });
    setLoading(btn, false, 'Cambiar email');
    if (!res) return;

    const data = await res.json();
    showFormMsg('msg-email', data.message ?? data.error, res.ok ? 'success' : 'error');
  });
}

// ── Helpers ───────────────────────────────────────────────────

function setLoading(btn, loading, text = '') {
  if (!btn) return;
  btn.dataset.loading = loading;
  if (text) btn.textContent = text;
}

function showFormMsg(id, msg, type = 'error') {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.className   = `alert alert--${type} visible`;
  setTimeout(() => { el.className = 'alert'; }, 5000);
}
