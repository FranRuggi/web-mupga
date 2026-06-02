/* MuPGA — guild.js  |  depende de app.js, auth.js */
document.addEventListener('DOMContentLoaded', async () => {
  if (!GUILD_NAME) { renderError('No se especificó un guild.'); return; }

  const data = await apiFetch(`guild.php?name=${encodeURIComponent(GUILD_NAME)}`);
  if (!data || data.error) { renderError(data?.error ?? 'Guild no encontrado.'); return; }

  document.getElementById('guild-name').textContent = data.name;
  document.getElementById('guild-meta').textContent =
    `Master: ${data.master} · ${data.score.toLocaleString('es-AR')} puntos · ${data.count} miembros`;

  document.getElementById('guild-container').innerHTML = `

    <div class="profile-card profile-avatar-card">
      <div class="profile-guild-name" style="font-size:2rem">🏰</div>
      <div class="profile-stat-row"><span class="profile-stat-label">Resets totales</span>
        <span class="profile-stat-value">${data.score.toLocaleString('es-AR')}</span></div>
      <div class="profile-stat-row"><span class="profile-stat-label">Miembros</span>
        <span class="profile-stat-value">${data.count}</span></div>
      <div class="profile-stat-row"><span class="profile-stat-label">Master</span>
        <span class="profile-stat-value">
          <a class="rank-name-link" href="${BASE}/player/?name=${encodeURIComponent(data.master)}">${esc(data.master)}</a>
        </span></div>
    </div>

    <div class="profile-card">
      <p class="account-card__title">Miembros</p>
      <div class="profile-stats-grid">
        ${data.members.map(m => `
          <div class="profile-stat-row">
            <span class="profile-stat-label">
              <a class="rank-name-link" href="${BASE}/player/?name=${encodeURIComponent(m.name)}">${esc(m.name)}</a>
              ${m.name === data.master ? ' 👑' : ''}
            </span>
            <span class="profile-stat-value">${esc(className(m.class))} · Nv${m.level} · ${m.resets} RST</span>
          </div>`).join('')}
      </div>
    </div>`;
});

function renderError(msg) {
  document.getElementById('guild-meta').textContent = '';
  document.getElementById('guild-container').innerHTML =
    `<p class="state-message" style="padding:3rem;text-align:center">${esc(msg)}</p>`;
}
