/* ============================================================
   MuPGA — app.js
   Vanilla JS: fetch helpers, renderers de componentes, utilidades
   ============================================================ */

// ── Config ──────────────────────────────────────────────────
const BASE = (document.documentElement.dataset.baseUrl || '/').replace(/\/$/, '');
const API  = `${BASE}/api`;

// ── Fetch helper ─────────────────────────────────────────────
async function apiFetch(endpoint) {
  try {
    const res = await fetch(`${API}/${endpoint}`, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (err) {
    console.warn(`[API] ${endpoint}:`, err.message);
    return null;
  }
}

// ── Seguridad: escape HTML ───────────────────────────────────
function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// ── Mapa de clases ───────────────────────────────────────────
const CLASS_NAMES = {
  0:  'Dark Wizard',      1:  'Soul Master',    3:  'Grand Master',
  7:  'Soul Wizard',
  16: 'Dark Knight',      17: 'Blade Knight',   19: 'Blade Master',   23: 'Dragon Knight',
  32: 'Fairy Elf',        33: 'Muse Elf',       35: 'High Elf',       39: 'Noble Elf',
  48: 'Magic Gladiator',  50: 'Duel Master',
  64: 'Dark Lord',        66: 'Lord Emperor',   70: 'Empire Lord',
  80: 'Summoner',         81: 'Bloody Summoner',83: 'Dimension Master',
  96: 'Rage Fighter',     98: 'Fist Master',
  112:'Grow Lancer',      114:'Mirage Lancer',
  128:'Rune Mage',        129:'Rune Spell Master',
  144:'Slayer',           145:'Royal Slayer',
  160:'Gun Crusher',      161:'Gun Breaker',
};

const CLASS_AVATAR = {
  0:'dw.jpg',  1:'dw.jpg',   3:'dw.jpg',   7:'dw.jpg',
  16:'dk.jpg', 17:'dk.jpg',  19:'dk.jpg',  23:'dk.jpg',
  32:'elf.jpg',33:'elf.jpg', 35:'elf.jpg', 39:'elf.jpg',
  48:'mg.jpg', 50:'mg.jpg',
  64:'dl.jpg', 66:'dl.jpg',  70:'dl.jpg',
  80:'sum.jpg',81:'sum.jpg', 83:'sum.jpg',
  96:'rf.jpg', 98:'rf.jpg',
  112:'gl.jpg',114:'gl.jpg',
  128:'rw.jpg',129:'rw.jpg',
  144:'sl.jpg',145:'sl.jpg',
  160:'avatar.jpg',161:'avatar.jpg',
};

function avatarSrc(classCode) {
  return `${BASE}/assets/img/class/${CLASS_AVATAR[classCode] || 'avatar.jpg'}`;
}

function className(classCode) {
  return CLASS_NAMES[classCode] || 'Desconocido';
}

// ── Skeleton helpers ─────────────────────────────────────────
function skeletonRankRows(n) {
  return Array.from({ length: n }, () => `
    <div class="rank-item">
      <span class="skeleton" style="height:1rem;width:1.5rem;border-radius:4px"></span>
      <span class="skeleton" style="height:40px;width:40px;border-radius:4px"></span>
      <div>
        <div class="skeleton" style="height:0.9rem;width:90px;margin-bottom:0.3rem;border-radius:4px"></div>
        <div class="skeleton" style="height:0.7rem;width:65px;border-radius:4px"></div>
      </div>
      <div class="skeleton" style="height:1.2rem;width:45px;border-radius:4px"></div>
    </div>
  `).join('');
}

// ── Online counter (count-up) ────────────────────────────────
async function loadOnlineCount() {
  const el = document.getElementById('online-count');
  if (!el) return;

  const data = await apiFetch('online.php');
  if (!data) { el.textContent = '—'; return; }

  const target   = Number(data.count) || 0;
  const duration = 900;
  const start    = performance.now();

  (function tick(now) {
    const p = Math.min((now - start) / duration, 1);
    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
    if (p < 1) requestAnimationFrame(tick);
  })(start);
}

// ── Server stats sidebar ─────────────────────────────────────
async function loadServerInfo() {
  const el = document.getElementById('server-stats');
  if (!el) return;

  const data = await apiFetch('serverinfo.php');
  if (!data) { el.innerHTML = '<p class="state-message" style="padding:0.5rem 0;font-size:0.75rem">Sin datos</p>'; return; }

  const rows = [
    { label: 'Temporada',  value: data.season },
    { label: 'EXP Rate',   value: data.exp    },
    { label: 'Drop Rate',  value: data.drop   },
    { label: 'Online',     value: data.players_online },
    { label: 'Registrados',value: data.players_total  },
  ];

  el.innerHTML = rows.map(r => `
    <div class="server-stat">
      <span class="server-stat__label">${esc(r.label)}</span>
      <span class="server-stat__value">${esc(r.value)}</span>
    </div>
  `).join('');
}

// ── Top players (home) ───────────────────────────────────────
async function loadTopPlayers() {
  const el = document.getElementById('top-players');
  if (!el) return;

  el.innerHTML = skeletonRankRows(3);

  const data = await apiFetch('rankings.php?type=resets&limit=3');
  if (!data || !data.length) {
    el.innerHTML = '<p class="state-message">Sin datos disponibles.</p>';
    return;
  }

  el.innerHTML = data.map((p, i) => `
    <div class="rank-item animate-in" style="animation-delay:${i * 0.1}s">
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
        <span class="rank-stat__num">${esc(p.resets)}</span>
        <span class="rank-stat__lbl">Resets</span>
      </div>
    </div>
  `).join('');
}

// ── Info cards (home) ────────────────────────────────────────
async function loadInfoCards() {
  const el = document.getElementById('info-cards');
  if (!el) return;

  const data = await apiFetch('serverinfo.php');
  if (!data) return;

  const cards = [
    { icon: '⚔️',  value: data.exp,            label: 'EXP Rate'       },
    { icon: '💎',  value: data.drop,           label: 'Drop Rate'      },
    { icon: '🌍',  value: data.season,         label: 'Temporada'      },
    { icon: '👥',  value: data.players_total,  label: 'Registrados'    },
    { icon: '🟢',  value: data.players_online, label: 'Online ahora'   },
    { icon: '🏰',  value: 'Semanal',           label: 'Castle Siege'   },
  ];

  el.innerHTML = cards.map((c, i) => `
    <div class="info-card animate-in" style="animation-delay:${i * 0.07}s">
      <div class="info-icon">${c.icon}</div>
      <div class="info-value">${esc(c.value)}</div>
      <div class="info-label">${esc(c.label)}</div>
    </div>
  `).join('');
}

// ── Nav: hamburger ───────────────────────────────────────────
function initNav() {
  const toggle = document.querySelector('.nav-toggle');
  const nav    = document.querySelector('.site-nav');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    toggle.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', open);
  });

  nav.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      nav.classList.remove('open');
      toggle.classList.remove('open');
    });
  });
}

// ── Active nav link ──────────────────────────────────────────
function markActiveNav() {
  const path = window.location.pathname;
  document.querySelectorAll('.nav-link[href]').forEach(link => {
    const href = link.getAttribute('href');
    if (href && path.endsWith(href.replace(/^\//, ''))) {
      link.classList.add('active');
    }
  });
}

// ── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initNav();
  markActiveNav();

  loadOnlineCount();
  loadServerInfo();
  loadTopPlayers();
  loadInfoCards();
});
