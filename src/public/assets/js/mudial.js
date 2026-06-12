/* ============================================================
   MuPGA — mudial.js
   Lógica del Prode: partidos, predicciones y ranking.
   Depende de app.js (BASE, API, apiFetch, esc) y auth.js
   ============================================================ */

const CUTOFF_SECS = 60 * 60; // 1 hora en segundos

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

function pointsBadge(points) {
  if (points === null || points === undefined) return '';
  if (points === 3) return '<span class="prode-badge prode-badge--exact">+3 Exacto</span>';
  if (points === 1) return '<span class="prode-badge prode-badge--winner">+1 Ganador</span>';
  return '<span class="prode-badge prode-badge--miss">0 pts</span>';
}

// ── Renderizado de partidos ────────────────────────────────────

function renderMatch(match) {
  const open  = isMatchOpen(match);
  const pred  = match.prediction;
  const done  = match.status === 'finished';
  const soon  = !open && match.status === 'pending';

  let scoreBlock = '';

  if (done) {
    // Partido terminado: mostrar resultado real vs predicción
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
    // Cerrado con predicción
    scoreBlock = `
      <div class="prode-match-score">
        <div class="prode-score-pred">${esc(pred.pred_score_home)} - ${esc(pred.pred_score_away)}</div>
        <div class="prode-score-label">Tu predicción <span class="prode-badge prode-badge--locked">Cerrado</span></div>
      </div>`;
  } else if (!pred && !open) {
    // Cerrado sin predicción
    scoreBlock = `
      <div class="prode-match-score">
        <div class="prode-score-label"><span class="prode-badge prode-badge--locked">Cerrado · Sin predicción</span></div>
      </div>`;
  } else {
    // Abierto: formulario de predicción
    const homeVal = pred ? pred.pred_score_home : '';
    const awayVal = pred ? pred.pred_score_away : '';
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
        <span class="prode-match-date">${esc(formatMatchDate(match.match_datetime_utc))}</span>
      </div>
      <div class="prode-match-body">
        <div class="prode-team prode-team--home">${esc(match.team_home)}</div>
        <div class="prode-match-center">${scoreBlock}</div>
        <div class="prode-team prode-team--away">${esc(match.team_away)}</div>
      </div>
    </div>`;
}

function renderMatchGroup(stage, matches) {
  return `
    <div class="prode-stage-group">
      <h2 class="prode-stage-title">${esc(stage)}</h2>
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

  // Agrupar por stage
  const groups = {};
  for (const m of all) {
    if (!groups[m.stage]) groups[m.stage] = [];
    groups[m.stage].push(m);
  }

  container.innerHTML = Object.entries(groups)
    .map(([stage, matches]) => renderMatchGroup(stage, matches))
    .join('');

  // Eventos de formularios
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
    loadMatches(); // Recargar para ver el estado actualizado
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
