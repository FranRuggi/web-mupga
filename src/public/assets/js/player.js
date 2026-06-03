/* ============================================================
   MuPGA — player.js
   Perfil público de un personaje. Lee PROFILE_CHAR (inyectado
   por player/index.php) y consulta api/player.php?name=X.
   Depende de app.js (BASE, API, apiFetch, esc, avatarSrc, className)
   ============================================================ */

document.addEventListener('DOMContentLoaded', async () => {
  const charName = PROFILE_CHAR || new URLSearchParams(window.location.search).get('name') || '';
  if (!charName) {
    renderError('No se especificó un personaje.');
    return;
  }

  const data = await apiFetch(`player.php?name=${encodeURIComponent(charName)}`);

  if (!data || data.error) {
    renderError(data?.error ?? 'Personaje no encontrado.');
    return;
  }

  renderProfile(data);
});

// ── Render principal ──────────────────────────────────────────

function renderProfile(p) {
  // Hero
  document.getElementById('profile-name').textContent  = p.name;
  document.getElementById('profile-class').textContent = className(p.class);

  const container = document.getElementById('profile-container');
  container.innerHTML = `

    <!-- Columna izquierda: avatar + estado -->
    <div class="profile-card profile-avatar-card">
      <img class="profile-avatar-img"
           src="${avatarSrc(p.class)}"
           alt="${esc(className(p.class))}"
           onerror="this.src='${BASE}/assets/img/class/avatar.jpg'">

      <div class="profile-status ${p.is_online ? 'profile-status--online' : 'profile-status--offline'}">
        ${p.is_online ? '● En línea' : '○ Desconectado'}
      </div>

      ${p.guild ? `
        <div class="profile-guild">
          <span class="profile-guild-label">Guild</span>
          <span class="profile-guild-name">${esc(p.guild)}</span>
        </div>` : ''}

      ${pkBadge(p.pk_level, p.pk_count)}
    </div>

    <!-- Columna derecha: stats -->
    <div class="profile-card profile-stats-card">
      <p class="account-card__title">${esc(p.name)}</p>

      <div class="profile-stats-grid">
        ${statRow('Clase',         className(p.class))}
        ${statRow('Nivel',         p.level.toLocaleString('es-AR'))}
        ${statRow('Nivel Maestro', p.master_level.toLocaleString('es-AR'))}
        ${statRow('Resets',        p.resets.toLocaleString('es-AR'))}
        ${statRow('Grand Resets',  p.master_resets.toLocaleString('es-AR'))}
      </div>

      <div class="profile-divider"></div>

      <p class="profile-section-title">Estadísticas</p>
      <div class="profile-stats-grid">
        ${statRow('Fuerza',    p.str.toLocaleString('es-AR'))}
        ${statRow('Agilidad',  p.agi.toLocaleString('es-AR'))}
        ${statRow('Vitalidad', p.vit.toLocaleString('es-AR'))}
        ${statRow('Energía',   p.ene.toLocaleString('es-AR'))}
        ${p.cmd > 0 ? statRow('Liderazgo', p.cmd.toLocaleString('es-AR')) : ''}
        ${statRow('Asesinatos', p.pk_count.toLocaleString('es-AR'))}
      </div>
    </div>

  `;
}

// ── Helpers ───────────────────────────────────────────────────

function statRow(label, value) {
  return `
    <div class="profile-stat-row">
      <span class="profile-stat-label">${esc(label)}</span>
      <span class="profile-stat-value">${esc(String(value))}</span>
    </div>`;
}

function pkBadge(pkLevel, pkCount) {
  if (pkCount === 0) return '';
  const labels = {
    0: ['Hero',    '#00b4d8'],
    1: ['Asesino', '#ff6b35'],
    2: ['Asesino', '#ff6b35'],
    3: ['Normal',  'var(--text-dim)'],
    4: ['Murderer','#e05757'],
    5: ['Murderer','#e05757'],
    6: ['Murderer','#e05757'],
  };
  const [label, color] = labels[pkLevel] ?? ['PK', '#e05757'];
  return `
    <div class="profile-pk-badge" style="color:${color};border-color:${color}">
      ${esc(label)} · ${pkCount} kills
    </div>`;
}

function renderError(msg) {
  document.getElementById('profile-class').textContent = '';
  document.getElementById('profile-container').innerHTML =
    `<p class="state-message" style="padding:3rem 0;text-align:center">${esc(msg)}</p>`;
}
