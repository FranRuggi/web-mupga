/* ============================================================
   MuPGA — mudial.js
   Lógica del Prode: partidos, predicciones y ranking.
   Depende de app.js (BASE, API, apiFetch, esc) y auth.js
   ============================================================ */

const CUTOFF_SECS = 60 * 60; // 1 hora en segundos

// ── Banderas (flagcdn.com) y composición de grupos ────────────

// Código ISO 3166-1 alpha-2 para cada equipo (gb-eng/gb-sct son excepciones de flagcdn)
const TEAM_FLAGS = {
  'Alemania': 'de',       'Curazao': 'cw',       'Costa de Marfil': 'ci',
  'Ecuador': 'ec',        'Países Bajos': 'nl',   'Japón': 'jp',
  'Suecia': 'se',         'Túnez': 'tn',          'España': 'es',
  'Cabo Verde': 'cv',     'Arabia Saudita': 'sa', 'Uruguay': 'uy',
  'Bélgica': 'be',        'Egipto': 'eg',         'Irán': 'ir',
  'Nueva Zelanda': 'nz',  'Francia': 'fr',        'Senegal': 'sn',
  'Irak': 'iq',           'Noruega': 'no',        'Argentina': 'ar',
  'Argelia': 'dz',        'Austria': 'at',        'Jordania': 'jo',
  'Portugal': 'pt',       'RD Congo': 'cd',       'Uzbekistán': 'uz',
  'Colombia': 'co',       'Inglaterra': 'gb-eng', 'Croacia': 'hr',
  'Ghana': 'gh',          'Panamá': 'pa',         'Chequia': 'cz',
  'Sudáfrica': 'za',      'Corea del Sur': 'kr',  'Suiza': 'ch',
  'Bosnia y Herz.': 'ba', 'Canadá': 'ca',         'Qatar': 'qa',
  'México': 'mx',         'EE.UU.': 'us',         'Australia': 'au',
  'Escocia': 'gb-sct',    'Brasil': 'br',         'Haití': 'ht',
  'Turquía': 'tr',        'Paraguay': 'py',       'Marruecos': 'ma',
};

const GROUP_TEAMS = {
  'Grupo A': ['México', 'Sudáfrica', 'Corea del Sur', 'Chequia'],
  'Grupo B': ['Canadá', 'Bosnia y Herz.', 'Suiza', 'Qatar'],
  'Grupo C': ['Brasil', 'Marruecos', 'Escocia', 'Haití'],
  'Grupo D': ['EE.UU.', 'Paraguay', 'Australia', 'Turquía'],
  'Grupo E': ['Alemania', 'Ecuador', 'Costa de Marfil', 'Curazao'],
  'Grupo F': ['Países Bajos', 'Japón', 'Suecia', 'Túnez'],
  'Grupo G': ['Bélgica', 'Irán', 'Egipto', 'Nueva Zelanda'],
  'Grupo H': ['España', 'Cabo Verde', 'Arabia Saudita', 'Uruguay'],
  'Grupo I': ['Francia', 'Senegal', 'Irak', 'Noruega'],
  'Grupo J': ['Argentina', 'Argelia', 'Austria', 'Jordania'],
  'Grupo K': ['Portugal', 'Colombia', 'Uzbekistán', 'RD Congo'],
  'Grupo L': ['Inglaterra', 'Croacia', 'Panamá', 'Ghana'],
};

// Orden canónico de stages (grupos primero, luego fases eliminatorias)
const STAGE_ORDER = [
  'Grupo A', 'Grupo B', 'Grupo C', 'Grupo D', 'Grupo E', 'Grupo F',
  'Grupo G', 'Grupo H', 'Grupo I', 'Grupo J', 'Grupo K', 'Grupo L',
  'Ronda de 32', 'Ronda de 16', 'Cuartos de Final', 'Semifinal',
  'Tercer Puesto', 'Final',
];

function teamFlag(name) {
  const code = TEAM_FLAGS[name];
  if (!code) return '';
  return `<img src="https://flagcdn.com/24x18/${code}.png" alt="${esc(name)}" title="${esc(name)}" class="prode-flag-img">`;
}

// ── Utilidades ────────────────────────────────────────────────

function showAlert(msg, type = 'error') {
  const el = document.getElementById('prode-alert');
  if (!el) return;
  el.className    = `alert alert--${type} visible`;
  el.textContent  = msg;
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideAlert() {
  const el = document.getElementById('prode-alert');
  if (el) el.className = 'alert';
}

function isMatchOpen(match) {
  if (match.is_locked || match.status !== 'pending') return false;
  const diffSecs = (new Date(match.match_datetime_utc + 'Z').getTime() - Date.now()) / 1000;
  return diffSecs > CUTOFF_SECS;
}

function formatMatchDate(utcStr) {
  return new Date(utcStr + 'Z').toLocaleString(undefined, {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function timingBadge(match) {
  const now    = Date.now();
  const kick   = new Date(match.match_datetime_utc + 'Z').getTime();
  const diffMs = kick - now;

  // EN VIVO: ya arrancó, menos de 110 min transcurridos, todavía pending
  if (diffMs < 0 && -diffMs < 110 * 60 * 1000 && match.status === 'pending') {
    return '<span class="prode-badge prode-badge--live">🟢 EN VIVO</span>';
  }

  // SE JUEGA PRONTO: abierto para predecir y faltan menos de 3 horas
  if (isMatchOpen(match) && diffMs > 0 && diffMs < 3 * 60 * 60 * 1000) {
    const totalMins = Math.floor(diffMs / 60000);
    const hours     = Math.floor(totalMins / 60);
    const mins      = totalMins % 60;
    const label     = hours > 0
      ? `⏰ En ${hours}h${mins > 0 ? ` ${mins}min` : ''}`
      : `⏰ En ${mins}min`;
    return `<span class="prode-badge prode-badge--soon">${label}</span>`;
  }

  return '';
}

function pointsBadge(points) {
  if (points === null || points === undefined) return '';
  if (points === 3) return '<span class="prode-badge prode-badge--exact">+3 Exacto</span>';
  if (points === 1) return '<span class="prode-badge prode-badge--winner">+1 Ganador</span>';
  return '<span class="prode-badge prode-badge--miss">0 pts</span>';
}

// ── Renderizado de partidos ────────────────────────────────────

function renderMatch(match) {
  const open   = isMatchOpen(match);
  const pred   = match.prediction;
  const done   = match.status === 'finished';
  const timing = timingBadge(match);

  let scoreBlock = '';

  if (done) {
    const realScore = `${match.score_home} - ${match.score_away}`;
    const predScore = pred
      ? `${pred.pred_score_home} - ${pred.pred_score_away}`
      : '<span class="prode-no-pred">Sin predicción</span>';

    scoreBlock = `
      <div class="prode-match-score">
        <div class="prode-score-real">${esc(realScore)}</div>
        <div class="prode-score-label">Resultado</div>
      </div>
      <div class="prode-match-divider"></div>
      <div class="prode-match-score">
        <div class="prode-score-pred">${pred ? esc(predScore) : predScore}</div>
        <div class="prode-score-label">Tu predicción ${pred ? pointsBadge(pred.points_earned) : ''}</div>
      </div>`;
  } else if (pred && !open) {
    scoreBlock = `
      <div class="prode-match-score">
        <div class="prode-score-pred">${esc(pred.pred_score_home)} - ${esc(pred.pred_score_away)}</div>
        <div class="prode-score-label">Tu predicción <span class="prode-badge prode-badge--locked">Cerrado</span></div>
      </div>`;
  } else if (!pred && !open) {
    scoreBlock = `
      <div class="prode-match-score">
        <div class="prode-score-label"><span class="prode-badge prode-badge--locked">Cerrado · Sin predicción</span></div>
      </div>`;
  } else {
    const homeVal  = pred ? pred.pred_score_home : '';
    const awayVal  = pred ? pred.pred_score_away : '';
    const btnLabel = pred ? 'Actualizar' : 'Predecir';

    scoreBlock = `
      <form class="prode-predict-form" data-match-id="${match.id}">
        <input type="number" class="form-input prode-score-input" name="score_home"
               min="0" max="30" placeholder="0" value="${homeVal}"
               aria-label="Goles ${esc(match.team_home)}" required>
        <span class="prode-score-sep">—</span>
        <input type="number" class="form-input prode-score-input" name="score_away"
               min="0" max="30" placeholder="0" value="${awayVal}"
               aria-label="Goles ${esc(match.team_away)}" required>
        <button type="submit" class="btn btn-primary btn-sm prode-predict-btn">${btnLabel}</button>
      </form>`;
  }

  return `
    <div class="prode-match-card animate-in${done ? ' prode-match-card--done' : ''}">
      <div class="prode-match-header">
        <span class="prode-stage-label">${esc(match.stage)}</span>
        <div class="prode-match-header-right">
          <span class="prode-match-date">${esc(formatMatchDate(match.match_datetime_utc))}</span>
          ${timing}
        </div>
      </div>
      <div class="prode-match-body">
        <div class="prode-team prode-team--home">${teamFlag(match.team_home)}${esc(match.team_home)}</div>
        <div class="prode-match-center">${scoreBlock}</div>
        <div class="prode-team prode-team--away">${teamFlag(match.team_away)}${esc(match.team_away)}</div>
      </div>
    </div>`;
}

function renderMatchGroup(stage, matches) {
  const teams = GROUP_TEAMS[stage] ?? [];
  const flagsHtml = teams.length
    ? ` <span class="prode-stage-flags">${
        teams.map(t => {
          const code = TEAM_FLAGS[t];
          return code
            ? `<img src="https://flagcdn.com/24x18/${code}.png" alt="${esc(t)}" title="${esc(t)}" class="prode-flag-img">`
            : '';
        }).join('')
      }</span>`
    : '';

  return `
    <div class="prode-stage-group">
      <h2 class="prode-stage-title">${esc(stage)}${flagsHtml}</h2>
      ${matches.map(renderMatch).join('')}
    </div>`;
}

function renderMatches(data) {
  const container = document.getElementById('matches-container');
  const all       = [...data.upcoming, ...data.finished];

  if (!all.length) {
    container.innerHTML = '<p class="state-message">Todavía no hay partidos cargados.</p>';
    return;
  }

  const groups = {};
  for (const m of all) {
    if (!groups[m.stage]) groups[m.stage] = [];
    groups[m.stage].push(m);
  }

  // Ordenar partidos dentro de cada grupo por fecha ASC
  for (const stage of Object.keys(groups)) {
    groups[stage].sort((a, b) =>
      new Date(a.match_datetime_utc + 'Z') - new Date(b.match_datetime_utc + 'Z')
    );
  }

  // Ordenar grupos según STAGE_ORDER; stages desconocidos van al final
  container.innerHTML = Object.entries(groups)
    .sort(([stageA], [stageB]) => {
      const ia = STAGE_ORDER.indexOf(stageA);
      const ib = STAGE_ORDER.indexOf(stageB);
      return (ia === -1 ? 999 : ia) - (ib === -1 ? 999 : ib);
    })
    .map(([stage, matches]) => renderMatchGroup(stage, matches))
    .join('');

  container.querySelectorAll('.prode-predict-form').forEach(form => {
    form.addEventListener('submit', handlePredict);
  });
}

// ── Envío de predicción ───────────────────────────────────────

async function handlePredict(e) {
  e.preventDefault();
  const form    = e.currentTarget;
  const matchId = parseInt(form.dataset.matchId, 10);
  const homeVal = form.querySelector('[name="score_home"]').value.trim();
  const awayVal = form.querySelector('[name="score_away"]').value.trim();

  const scoreHome = parseInt(homeVal, 10);
  const scoreAway = parseInt(awayVal, 10);

  if (isNaN(scoreHome) || isNaN(scoreAway) || scoreHome < 0 || scoreAway < 0) {
    showAlert('Ingresá goles válidos (números no negativos).', 'error');
    return;
  }

  const btn = form.querySelector('.prode-predict-btn');
  btn.setAttribute('data-loading', 'true');
  hideAlert();

  const res = await authFetch('prode/predict.php', {
    method: 'POST',
    body: JSON.stringify({ match_id: matchId, score_home: scoreHome, score_away: scoreAway }),
  });

  btn.removeAttribute('data-loading');

  if (!res) return; // authFetch ya redirigió a login si era 401

  if (res.ok) {
    showAlert('¡Predicción guardada!', 'success');
    loadMatches();
  } else {
    const data = await res.json().catch(() => ({}));
    showAlert(data.error ?? 'Error al guardar la predicción.', 'error');
  }
}

// ── Ranking ───────────────────────────────────────────────────

function renderRankingRow(player, pos) {
  const medals = ['🥇', '🥈', '🥉'];
  const posLabel = pos <= 3
    ? `<span title="Posición ${pos}">${medals[pos - 1]}</span>`
    : `<span class="rank-pos">${pos}</span>`;

  let rowClass = 'prode-rank-row';
  if (pos === 1) rowClass += ' prode-rank-row--gold';
  if (pos === 2) rowClass += ' prode-rank-row--silver';
  if (pos === 3) rowClass += ' prode-rank-row--bronze';

  return `
    <div class="${rowClass} animate-in" style="animation-delay:${Math.min(pos - 1, 10) * 0.03}s">
      <span class="prode-rank-pos">${posLabel}</span>
      <span class="prode-rank-account">${esc(player.account)}</span>
      <span class="prode-rank-pts">${esc(player.total_points)}</span>
      <span class="prode-rank-exact">${esc(player.exact_hits)}</span>
      <span class="prode-rank-winner">${esc(player.winner_hits)}</span>
    </div>`;
}

async function loadRanking() {
  const container = document.getElementById('ranking-container');
  container.innerHTML = '<div class="prode-loading">' +
    Array.from({ length: 8 }, () =>
      '<div class="skeleton" style="height:52px;border-radius:8px;margin-bottom:0.5rem"></div>'
    ).join('') + '</div>';

  const data = await apiFetch('prode/ranking.php');

  if (!data || !data.length) {
    container.innerHTML = '<p class="state-message">El ranking está vacío por ahora.</p>';
    return;
  }

  container.innerHTML = `
    <div class="prode-ranking-table">
      <div class="prode-rank-header">
        <span>#</span>
        <span>Cuenta</span>
        <span>Puntos</span>
        <span>Exactos</span>
        <span>Ganador</span>
      </div>
      ${data.map((p, i) => renderRankingRow(p, i + 1)).join('')}
    </div>`;
}

// ── Carga de partidos ─────────────────────────────────────────

async function loadMatches() {
  const container = document.getElementById('matches-container');

  const res = await authFetch('prode/matches.php');
  if (!res) return;

  if (!res.ok) {
    container.innerHTML = '<p class="state-message">Error al cargar los partidos.</p>';
    return;
  }

  const data = await res.json().catch(() => null);
  if (!data) return;

  renderMatches(data);
}

// ── Tabs ──────────────────────────────────────────────────────

function initTabs() {
  const tabNav = document.getElementById('prode-tabs');
  if (!tabNav) return;

  let rankingLoaded = false;

  tabNav.addEventListener('click', e => {
    const btn = e.target.closest('.tab-btn');
    if (!btn) return;

    const tab = btn.dataset.tab;

    tabNav.querySelectorAll('.tab-btn').forEach(b =>
      b.classList.toggle('active', b.dataset.tab === tab)
    );

    document.getElementById('panel-matches').hidden = (tab !== 'matches');
    document.getElementById('panel-ranking').hidden  = (tab !== 'ranking');

    if (tab === 'ranking' && !rankingLoaded) {
      rankingLoaded = true;
      loadRanking();
    }
  });
}

// ── Init ──────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  if (typeof isAuthenticated !== 'function' || !isAuthenticated()) {
    window.location.href = `${BASE}/login/?redirect=mudial`;
    return;
  }

  initTabs();
  loadMatches();
});
