/* ============================================================
   MuPGA — rankings.js
   Lógica de la página de rankings (tabs + renderizado)
   Depende de app.js (BASE, API, apiFetch, esc, avatarSrc, className)
   y auth.js (getUser, isAuthenticated)
   ============================================================ */

const RANKINGS_LIMIT   = 100;
const REFRESH_INTERVAL = 2 * 60 * 1000;
let   currentType      = 'resets';

// Caché en memoria: evita re-fetch al cambiar de tab en los primeros 2 min
const _cache = new Map(); // url → {raw, ts}
async function cachedFetch(url) {
  const hit = _cache.get(url);
  if (hit && Date.now() - hit.ts < REFRESH_INTERVAL) return hit.raw;
  const raw = await apiFetch(url);
  if (raw) _cache.set(url, { raw, ts: Date.now() });
  return raw;
}

// ISO 2 → emoji de bandera (ej: "AR" → 🇦🇷)
const flag = iso => iso
  ? String.fromCodePoint(...[...iso.toUpperCase()].map(c => 0x1F1E0 + c.charCodeAt(0) - 65))
  : '';

const TABS = [
  { type: 'resets',       label: 'Resets',       stat: 'resets',       statLabel: 'Resets'    },
  { type: 'level',        label: 'Nivel',         stat: 'level',        statLabel: 'Nivel'     },
  { type: 'masterresets', label: 'Master Resets', stat: 'masterResets', statLabel: 'M. Resets' },
  { type: 'kills',        label: 'PK Killers',    stat: 'pkCount',      statLabel: 'Kills'     },
  { type: 'guilds',       label: 'Guilds',        stat: 'score',        statLabel: 'Puntos'    },
];

// ── Renderizadores ────────────────────────────────────────
function renderPlayerRow(p, pos, statKey, statLabel) {
  const isMe = p.isPlayer === true;
  return `
    <div class="rank-item animate-in${isMe ? ' rank-item--me' : ''}" style="animation-delay:${Math.min(pos - 1, 8) * 0.04}s">
      <span class="rank-pos">${pos}</span>
      <img class="rank-avatar"
           src="${avatarSrc(p.class)}"
           alt="${esc(className(p.class))}"
           onerror="this.src='${BASE}/assets/img/class/avatar.jpg'"
           loading="lazy">
      <div>
        <div class="rank-name">
          ${p.country ? `<span title="${esc(p.country)}">${flag(p.country)}</span> ` : ''}
          <a class="rank-name-link" href="${BASE}/player/?name=${encodeURIComponent(p.name)}">${esc(p.name)}</a>
          ${isMe ? '<span class="rank-me-badge">vos</span>' : ''}
        </div>
        <div class="rank-class">${esc(className(p.class))}</div>
      </div>
      <div class="rank-stat">
        <span class="rank-stat__num">${esc(p[statKey] ?? 0)}</span>
        <span class="rank-stat__lbl">${statLabel}</span>
      </div>
    </div>`;
}

function renderGuildRow(g, i) {
  return `
    <div class="rank-item animate-in" style="animation-delay:${Math.min(i, 8) * 0.04}s">
      <span class="rank-pos">${i + 1}</span>
      <div style="display:flex;align-items:center;justify-content:center;width:40px;height:40px;font-size:1.4rem">🏰</div>
      <div>
        <div class="rank-name"><a class="rank-name-link" href="${BASE}/guild/?name=${encodeURIComponent(g.name)}">${esc(g.name)}</a></div>
        <div class="rank-class">Master: <a class="rank-name-link" href="${BASE}/player/?name=${encodeURIComponent(g.master)}">${esc(g.master)}</a></div>
      </div>
      <div class="rank-stat">
        <span class="rank-stat__num">${esc(g.score)}</span>
        <span class="rank-stat__lbl">Puntos</span>
      </div>
    </div>`;
}

// ── Renderiza la lista completa + entrada del jugador fuera de top ────────
function renderData(rows, player, type) {
  const tabCfg = TABS.find(t => t.type === type) ?? TABS[0];
  const list   = document.createElement('div');
  list.className = 'ranking-list';

  if (type === 'guilds') {
    list.innerHTML = rows.map((g, i) => renderGuildRow(g, i)).join('');
    return list.outerHTML;
  }

  list.innerHTML = rows.map((p, i) => renderPlayerRow(p, i + 1, tabCfg.stat, tabCfg.statLabel)).join('');

  // Si el jugador no aparece en el top, mostrar su entrada separada al final
  if (player && !rows.some(r => r.isPlayer)) {
    const sep = document.createElement('div');
    sep.className = 'rank-separator';
    sep.textContent = `Tu posición: #${player.position}`;
    list.insertAdjacentHTML('beforeend', sep.outerHTML + renderPlayerRow(
      { ...player, isPlayer: true },
      player.position,
      tabCfg.stat,
      tabCfg.statLabel
    ));
  }

  return list.outerHTML;
}

// ── Actualiza timestamp ───────────────────────────────────
function updateTimestamp() {
  const el = document.getElementById('refresh-ts');
  if (el) el.textContent = new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
}

// ── Construye la URL con el account del jugador si está logueado ──────────
function rankingUrl(type) {
  const user    = typeof getUser === 'function' ? getUser() : null;
  const account = user?.username ?? '';
  const base    = `rankings.php?type=${encodeURIComponent(type)}&limit=${RANKINGS_LIMIT}`;
  return account ? `${base}&account=${encodeURIComponent(account)}` : base;
}

// ── Carga inicial ─────────────────────────────────────────
async function loadRanking(type) {
  currentType = type;

  document.querySelectorAll('.tab-btn').forEach(btn =>
    btn.classList.toggle('active', btn.dataset.type === type)
  );

  const container = document.getElementById('rankings-container');
  container.innerHTML = `<div class="rankings-loading">${skeletonRankRows(8)}</div>`;

  const raw = await cachedFetch(rankingUrl(type));
  if (!raw) {
    container.innerHTML = '<p class="state-message">Sin datos disponibles para este ranking.</p>';
    return;
  }

  // La API devuelve array plano (guilds / sin account) u objeto {rows, player}
  const rows   = Array.isArray(raw) ? raw   : (raw.rows   ?? []);
  const player = Array.isArray(raw) ? null  : (raw.player ?? null);

  if (!rows.length) {
    container.innerHTML = '<p class="state-message">Sin datos disponibles para este ranking.</p>';
    return;
  }

  container.innerHTML = renderData(rows, player, type);
  updateTimestamp();
}

// ── Refresh silencioso ────────────────────────────────────
async function silentRefresh() {
  const raw = await cachedFetch(rankingUrl(currentType));
  if (!raw) return;

  const container = document.getElementById('rankings-container');
  if (!container) return;

  const rows   = Array.isArray(raw) ? raw  : (raw.rows   ?? []);
  const player = Array.isArray(raw) ? null : (raw.player ?? null);
  if (!rows.length) return;

  container.innerHTML = renderData(rows, player, currentType);
  updateTimestamp();
}

// ── Init ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const nav = document.getElementById('tab-nav');
  if (!nav) return;

  nav.innerHTML = TABS.map(t =>
    `<button class="tab-btn${t.type === currentType ? ' active' : ''}" data-type="${t.type}">${t.label}</button>`
  ).join('');

  nav.addEventListener('click', e => {
    const btn = e.target.closest('.tab-btn');
    if (btn) loadRanking(btn.dataset.type);
  });

  loadRanking(currentType);
  setInterval(silentRefresh, REFRESH_INTERVAL);
});
