/* ============================================================
   MuPGA — rankings.js
   Lógica de la página de rankings (tabs + renderizado)
   Depende de app.js (BASE, API, apiFetch, esc, avatarSrc, className, skeletonRankRows)
   ============================================================ */

const RANKINGS_LIMIT   = 50;
const REFRESH_INTERVAL = 2 * 60 * 1000; // 2 minutos en ms
let   currentType      = 'resets';

// ── Configuración de tabs ─────────────────────────────────────
const TABS = [
  { type: 'resets',       label: 'Resets',       stat: 'resets',      statLabel: 'Resets'    },
  { type: 'level',        label: 'Nivel',         stat: 'level',       statLabel: 'Nivel'     },
  { type: 'masterresets', label: 'Master Resets', stat: 'masterResets',statLabel: 'M. Resets' },
  { type: 'kills',        label: 'PK Killers',    stat: 'pkCount',     statLabel: 'Kills'     },
  { type: 'guilds',       label: 'Guilds',        stat: 'score',       statLabel: 'Puntos'    },
];

// ── Renderizadores ────────────────────────────────────────────
function renderPlayerRow(p, i, statKey, statLabel) {
  return `
    <div class="rank-item animate-in" style="animation-delay:${Math.min(i,8) * 0.04}s">
      <span class="rank-pos">${i + 1}</span>
      <img class="rank-avatar"
           src="${avatarSrc(p.class)}"
           alt="${esc(className(p.class))}"
           onerror="this.src='${BASE}/assets/img/class/avatar.jpg'"
           loading="lazy">
      <div>
        <div class="rank-name">${esc(p.name)}</div>
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
    <div class="rank-item animate-in" style="animation-delay:${Math.min(i,8) * 0.04}s">
      <span class="rank-pos">${i + 1}</span>
      <div style="display:flex;align-items:center;justify-content:center;width:40px;height:40px;font-size:1.4rem">🏰</div>
      <div>
        <div class="rank-name">${esc(g.name)}</div>
        <div class="rank-class">Master: ${esc(g.master)}</div>
      </div>
      <div class="rank-stat">
        <span class="rank-stat__num">${esc(g.score)}</span>
        <span class="rank-stat__lbl">Puntos</span>
      </div>
    </div>`;
}

// ── Renderiza los datos en el contenedor ──────────────────────
function renderData(data, type) {
  const tabCfg = TABS.find(t => t.type === type) ?? TABS[0];

  if (type === 'guilds') {
    return `<div class="ranking-list">${data.map((g, i) => renderGuildRow(g, i)).join('')}</div>`;
  }
  return `<div class="ranking-list">${data.map((p, i) => renderPlayerRow(p, i, tabCfg.stat, tabCfg.statLabel)).join('')}</div>`;
}

// ── Actualiza el timestamp de última actualización ────────────
function updateTimestamp() {
  const el = document.getElementById('refresh-ts');
  if (el) el.textContent = new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
}

// ── Carga inicial (con skeleton) ──────────────────────────────
async function loadRanking(type) {
  currentType = type;

  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.type === type);
  });

  const container = document.getElementById('rankings-container');
  container.innerHTML = `<div class="rankings-loading">${skeletonRankRows(8)}</div>`;

  const data = await apiFetch(`rankings.php?type=${encodeURIComponent(type)}&limit=${RANKINGS_LIMIT}`);

  if (!data || !data.length) {
    container.innerHTML = '<p class="state-message">Sin datos disponibles para este ranking.</p>';
    return;
  }

  container.innerHTML = renderData(data, type);
  updateTimestamp();
}

// ── Refresh silencioso cada 2 minutos ─────────────────────────
// No muestra skeleton — si falla, conserva los datos anteriores
async function silentRefresh() {
  const data = await apiFetch(`rankings.php?type=${encodeURIComponent(currentType)}&limit=${RANKINGS_LIMIT}`);
  if (!data || !data.length) return;

  const container = document.getElementById('rankings-container');
  if (!container) return;

  container.innerHTML = renderData(data, currentType);
  updateTimestamp();
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const nav = document.getElementById('tab-nav');
  if (!nav) return;

  // Construir tabs dinámicamente
  nav.innerHTML = TABS.map(t =>
    `<button class="tab-btn${t.type === currentType ? ' active' : ''}" data-type="${t.type}">${t.label}</button>`
  ).join('');

  nav.addEventListener('click', e => {
    const btn = e.target.closest('.tab-btn');
    if (btn) loadRanking(btn.dataset.type);
  });

  // Carga inicial
  loadRanking(currentType);

  // Auto-refresh cada 2 minutos (sin skeleton, silencioso)
  setInterval(silentRefresh, REFRESH_INTERVAL);
});
