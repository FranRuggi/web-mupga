/* ============================================================
   MuPGA — usercp.js
   Panel de cuenta: carga perfil, personajes, balance.
   Depende de: auth.js, app.js (BASE, API, esc, avatarSrc, className)
   ============================================================ */

document.addEventListener('DOMContentLoaded', async () => {
  if (!isAuthenticated()) {
    window.location.replace(`${BASE}/login/`);
    return;
  }

  const user = getUser();
  if (user) {
    const el = document.getElementById('usercp-username');
    if (el) el.textContent = user.username;
  }

  await Promise.all([loadProfile(), loadBalance()]);

  initChangePassword();
  initChangeEmail();
  initGameOptions();
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
    // AccountLevel 3 = único nivel VIP activo en MuPGA
    const isVip = (data.account_level ?? 0) === 3;
    vipEl.innerHTML = isVip
      ? '<span class="badge badge--vip">⭐ VIP activo</span>'
      : '<span class="badge badge--normal">Sin VIP</span>';

    const expireRow = document.getElementById('info-vip-expire-row');
    const expireEl  = document.getElementById('info-vip-expire');
    if (isVip && data.expire_date && expireRow && expireEl) {
      expireEl.textContent = new Date(data.expire_date).toLocaleDateString('es-AR');
      expireRow.style.display = '';
    }
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

// Lista de personajes cargados (para el selector de opciones de juego)
let _characters = [];

function renderCharacters(chars) {
  _characters = chars;
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
        <span class="char-zen">· ${(c.zen ?? 0).toLocaleString('es-AR')} Zen</span>
      </div>
    </div>
  `).join('');

  // Sincronizar selector del panel de opciones
  populateCharSelect(chars);
}

// ── Panel de Opciones de personaje ───────────────────────────

function populateCharSelect(chars) {
  const sel = document.getElementById('char-select');
  if (!sel) return;

  const prevSelection = sel.value; // preservar personaje seleccionado

  const actionBtns = ['btn-unstick', 'btn-clearpk', 'btn-resetstats', 'btn-resetml', 'btn-resetchar'];

  if (!chars.length) {
    sel.innerHTML = '<option value="">Sin personajes</option>';
    actionBtns.forEach(id => document.getElementById(id)?.toggleAttribute('disabled', true));
    const card = document.getElementById('addstats-card');
    if (card) card.style.display = 'none';
    return;
  }

  sel.innerHTML = chars.map(c =>
    `<option value="${esc(c.name)}">${esc(c.name)} — ${esc(className(c.class))} Nv${c.level} (${c.resets} RST) · ${(c.zen ?? 0).toLocaleString('es-AR')} Zen</option>`
  ).join('');

  // Restaurar selección previa si el personaje aún existe
  if (prevSelection && chars.some(c => c.name === prevSelection)) {
    sel.value = prevSelection;
  }

  actionBtns.forEach(id => document.getElementById(id)?.toggleAttribute('disabled', false));

  updateAddStatsPanel();
}

function initGameOptions() {
  const actions = [
    ['btn-unstick',    'account/unstick.php',    'msg-unstick',    'Unstick'],
    ['btn-clearpk',    'account/clearpk.php',    'msg-clearpk',    'Limpiar PK'],
    ['btn-resetstats', 'account/resetstats.php', 'msg-resetstats', 'Resetear Stats'],
    ['btn-resetml',    'account/resetml.php',    'msg-resetml',    'Resetear Árbol ML'],
    ['btn-resetchar',  'account/resetchar.php',  'msg-resetchar',  'Reset personaje'],
  ];
  actions.forEach(([id, endpoint, msgId, label]) => {
    document.getElementById(id)?.addEventListener('click', () =>
      runCharAction(id.replace('btn-', ''), endpoint, msgId, label)
    );
  });

  // Actualizar sección add-stats al cambiar personaje
  document.getElementById('char-select')?.addEventListener('change', updateAddStatsPanel);

  // Inputs: recalcular total en tiempo real
  document.querySelectorAll('.addstats-input').forEach(inp =>
    inp.addEventListener('input', recalcAddStatsTotal)
  );

  // Submit agregar stats
  document.getElementById('form-addstats')?.addEventListener('submit', handleAddStats);
}

function updateAddStatsPanel() {
  const sel    = document.getElementById('char-select');
  const card   = document.getElementById('addstats-card');
  const charName = sel?.value;
  if (!charName || !card) return;

  const char = _characters.find(c => c.name === charName);
  if (!char) return;

  card.style.display = '';
  document.getElementById('addstats-char-name').textContent = char.name;
  document.getElementById('addstats-available').textContent = char.level_up_point ?? '—';

  // Mostrar Liderazgo solo para Dark Lord (clases 64, 66, 70)
  const isDarkLord = [64, 66, 70].includes(char.class);
  const cmdRow = document.getElementById('add-cmd-row');
  if (cmdRow) cmdRow.style.display = isDarkLord ? '' : 'none';

  // Stats actuales del personaje
  const statsEl = document.getElementById('addstats-stats');
  if (statsEl) {
    const rows = [
      ['Fue', char.str], ['Agi', char.agi], ['Vit', char.vit], ['Ene', char.ene],
      ...(isDarkLord ? [['Lid', char.cmd]] : []),
    ];
    statsEl.innerHTML = rows.map(([label, val]) =>
      `<span class="current-stat">
        <span class="current-stat__label">${label}</span>
        <span class="current-stat__val">${(val ?? 0).toLocaleString('es-AR')}</span>
      </span>`
    ).join('');
  }

  // Resetear inputs
  document.querySelectorAll('.addstats-input').forEach(i => i.value = '0');
  recalcAddStatsTotal();
}

function recalcAddStatsTotal() {
  const total = ['add-str','add-agi','add-vit','add-ene','add-cmd']
    .reduce((s, id) => s + Math.max(0, parseInt(document.getElementById(id)?.value ?? 0, 10)), 0);
  const el = document.getElementById('addstats-total');
  if (el) el.textContent = total.toLocaleString('es-AR');
}

async function handleAddStats(e) {
  e.preventDefault();
  const sel      = document.getElementById('char-select');
  const charName = sel?.value;
  if (!charName) return;

  const btn = document.getElementById('btn-addstats');
  if (btn) { btn.disabled = true; btn.textContent = 'Aplicando...'; }

  // Capturar antes del fetch para actualizar localmente si tiene éxito
  const addStr = Math.max(0, parseInt(document.getElementById('add-str')?.value ?? 0, 10));
  const addAgi = Math.max(0, parseInt(document.getElementById('add-agi')?.value ?? 0, 10));
  const addVit = Math.max(0, parseInt(document.getElementById('add-vit')?.value ?? 0, 10));
  const addEne = Math.max(0, parseInt(document.getElementById('add-ene')?.value ?? 0, 10));
  const addCmd = Math.max(0, parseInt(document.getElementById('add-cmd')?.value ?? 0, 10));

  const res = await authFetch('account/addstats.php', {
    method: 'POST',
    body: JSON.stringify({ character: charName, str: addStr, agi: addAgi, vit: addVit, ene: addEne, cmd: addCmd }),
  });

  if (btn) { btn.disabled = false; btn.textContent = 'Agregar puntos'; }
  if (!res) return;

  const data = await res.json();
  showGameMsg('msg-addstats', data.message ?? data.error, res.ok ? 'success' : 'error');

  if (res.ok) {
    document.querySelectorAll('.addstats-input').forEach(i => i.value = '0');
    recalcAddStatsTotal();
    // Re-sincronizar con la DB para que stats y puntos queden exactos
    await loadProfile();
  }
}

async function runCharAction(actionId, endpoint, msgId, btnLabel) {
  const sel      = document.getElementById('char-select');
  const charName = sel?.value;
  if (!charName) return;

  const btn = document.getElementById(`btn-${actionId}`);
  if (btn) { btn.disabled = true; btn.querySelector('strong').textContent = '...'; }

  const res = await authFetch(endpoint, { method: 'POST', body: JSON.stringify({ character: charName }) });
  if (btn) { btn.disabled = false; btn.querySelector('strong').textContent = btnLabel; }

  if (!res) return;
  const data = await res.json();
  showGameMsg(msgId, data.message ?? data.error, res.ok ? 'success' : 'error');

  // Re-sincronizar con la DB para que stats y puntos queden exactos
  if (res.ok) await loadProfile();
}

function showGameMsg(id, msg, type) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.className   = `alert alert--${type} visible`;
  setTimeout(() => { el.className = 'alert'; }, 5000);
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
