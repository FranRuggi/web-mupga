/* ============================================================
   MuPGA — app.js
   Vanilla JS: fetch helpers, renderers de componentes, utilidades
   ============================================================ */

// ── Config ──────────────────────────────────────────────────
// BASE : para assets y links de navegación.
//   VPS all-in-one → PHP inyecta la URL completa (ej. https://api.mupga.com.ar)
//   Cloudflare Pages → PHP no corre, data-base-url="", BASE='' → assets del CDN
// API  : para fetch() al backend.
//   VPS all-in-one → mismo dominio que BASE
//   Cloudflare Pages → config.js provee https://api.mupga.com.ar
const _phpBase  = (document.documentElement.dataset.baseUrl ?? '').replace(/\/$/, '');
const _apiBase  = (typeof MUPGA_CONFIG !== 'undefined') ? MUPGA_CONFIG.api : '';
const BASE = _phpBase;
const API  = _phpBase ? `${_phpBase}/api` : (_apiBase ? `${_apiBase}/api` : '/api');

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
  // Dark Wizard
  0:  'Dark Wizard',      1:  'Soul Master',      2:  'Grand Master',     3:  'Grand Master',     7:  'Soul Wizard',
  // Dark Knight
  16: 'Dark Knight',      17: 'Blade Knight',     18: 'Blade Master',     19: 'Blade Master',     23: 'Dragon Knight',
  // Fairy Elf
  32: 'Fairy Elf',        33: 'Muse Elf',         34: 'High Elf',         35: 'High Elf',         39: 'Noble Elf',
  // Magic Gladiator
  48: 'Magic Gladiator',  50: 'Duel Master',      54: 'Magic Knight',
  // Dark Lord
  64: 'Dark Lord',        66: 'Lord Emperor',     70: 'Empire Lord',
  // Summoner
  80: 'Summoner',         81: 'Bloody Summoner',  83: 'Dimension Master', 87: 'Dimension Summoner',
  // Rage Fighter
  96: 'Rage Fighter',     98: 'Fist Master',      102:'Fist Blazer',
  // Grow Lancer
  112:'Grow Lancer',      114:'Mirage Lancer',    118:'Shining Lancer',
  // Rune Mage
  128:'Rune Mage',        129:'Rune Spell Master', 131:'Grand Rune Master', 135:'Majestic Rune Wizard',
  // Slayer
  144:'Slayer',           145:'Royal Slayer',     147:'Master Slayer',    151:'Slaughterer',
  // Gun Crusher
  160:'Gun Crusher',      161:'Gun Breaker',      163:'Master Gun Breaker', 167:'Heist Gun Crusher',
  // Light Wizard
  176:'Light Wizard',     177:'Light Master',     179:'Shining Wizard',   183:'Luminous Wizard',
  // Lemuria Mage
  192:'Lemuria Mage',     193:'Warmage',          195:'Archmage',         199:'Mystic Mage',
};

const CLASS_AVATAR = {
  0:'dw.jpg',  1:'dw.jpg',   3:'dw.jpg',   7:'dw.jpg',
  16:'dk.jpg', 17:'dk.jpg',  18:'dk.jpg',  19:'dk.jpg',  23:'dk.jpg',
  32:'elf.jpg',33:'elf.jpg', 34:'elf.jpg', 35:'elf.jpg', 39:'elf.jpg',
  48:'mg.jpg', 50:'mg.jpg',  54:'mg.jpg',
  64:'dl.jpg', 66:'dl.jpg',  70:'dl.jpg',
  80:'sum.jpg',81:'sum.jpg', 83:'sum.jpg', 87:'sum.jpg',
  96:'rf.jpg', 98:'rf.jpg',  102:'rf.jpg',
  112:'gl.jpg',114:'gl.jpg', 118:'gl.jpg',
  128:'rw.jpg',129:'rw.jpg', 131:'rw.jpg', 135:'rw.jpg',
  144:'sl.jpg',145:'sl.jpg', 147:'sl.jpg', 151:'sl.jpg',
  160:'avatar.jpg',161:'avatar.jpg',163:'avatar.jpg',167:'avatar.jpg',
  176:'avatar.jpg',177:'avatar.jpg',179:'avatar.jpg',183:'avatar.jpg',
  192:'avatar.jpg',193:'avatar.jpg',195:'avatar.jpg',199:'avatar.jpg',
};

function avatarSrc(classCode) {
  return `${BASE}/assets/img/class/${CLASS_AVATAR[classCode] || 'avatar.jpg'}`;
}

function className(classCode) {
  const n = parseInt(classCode, 10);
  return CLASS_NAMES[n] ?? (Number.isFinite(n) ? `Clase ${n}` : '—');
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

  const raw  = await apiFetch('rankings.php?type=resets&limit=3');
  // La API puede devolver array plano o {rows, player} — normalizar
  const rows = Array.isArray(raw) ? raw : (raw?.rows ?? []);

  if (!rows.length) {
    el.innerHTML = '<p class="state-message">Sin datos disponibles.</p>';
    return;
  }

  el.innerHTML = rows.map((p, i) => `
    <div class="rank-item animate-in" style="animation-delay:${i * 0.1}s">
      <span class="rank-pos">${i + 1}</span>
      <img class="rank-avatar"
           src="${avatarSrc(p.class)}"
           alt="${esc(className(p.class))}"
           onerror="this.src='${BASE}/assets/img/class/avatar.jpg'"
           loading="lazy">
      <div>
        <div class="rank-name">
          <a class="rank-name-link" href="${BASE}/player/?name=${encodeURIComponent(p.name)}">${esc(p.name)}</a>
        </div>
        <div class="rank-class">${esc(className(p.class))}</div>
      </div>
      <div class="rank-stat">
        <span class="rank-stat__num">${esc(p.resets)}</span>
        <span class="rank-stat__lbl">Resets</span>
      </div>
    </div>
  `).join('');
}

// ── Últimas noticias (home) ─────────────────────────────────
async function loadHomeNews() {
  const el = document.getElementById('home-news');
  if (!el) return;

  const data = await apiFetch('newsdata.php');
  if (!data?.length) return;

  el.innerHTML = data.slice(0, 3).map((n, i) => `
    <div class="card animate-in" style="animation-delay:${i * 0.08}s">
      <div class="card-body">
        <div class="card-meta">${esc(n.category)} · ${esc(n.date)}</div>
        <h3 class="card-title">${esc(n.title)}</h3>
        <p class="card-text">${esc(n.summary)}</p>
        <a class="card-link" href="${BASE}/news/#news-${i}">Leer más</a>
      </div>
    </div>`).join('');
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
    { icon: '🎮',  value: 'Eventos',           label: 'Semanales'   },
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

  const cta = document.getElementById('hero-cta');
  if (cta && typeof isAuthenticated === 'function' && isAuthenticated()) {
    cta.textContent = 'Mi cuenta';
    cta.href = `${BASE}/usercp/`;
  }

  loadOnlineCount();
  loadServerInfo();
  loadTopPlayers();
  loadInfoCards();
  loadHomeNews();
});
