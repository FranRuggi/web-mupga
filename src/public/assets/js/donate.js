/* ============================================================
   MuPGA — donate.js
   Renderiza los paquetes de WCoin desde /api/donate.php.
   El enlace de pago viene del servidor (DONATION_URL en .env).
   Depende de: app.js (BASE, API, apiFetch, esc)
   ============================================================ */

document.addEventListener('DOMContentLoaded', async () => {
  if (!isAuthenticated()) {
    window.location.replace(
      `${BASE}/login/?redirect=${encodeURIComponent(window.location.pathname)}`
    );
    return;
  }
  await loadDonateData();
});

async function loadDonateData() {
  const container = document.getElementById('donate-packages');
  const notice    = document.getElementById('donate-notice');
  if (!container) return;

  const data = await apiFetch('donate.php');

  if (!data) {
    container.innerHTML = '<p class="state-message">No se pudo cargar la información de donaciones.</p>';
    return;
  }

  // Mostrar aviso si el enlace de pago aún no está configurado
  if (!data.has_payment_link && notice) {
    notice.innerHTML = `
      <p>⚙️ El sistema de pagos está siendo configurado. ¡Volvé pronto!</p>`;
    notice.classList.add('visible');
  }

  container.innerHTML = (data.packages ?? []).map(pkg => `
    <div class="donate-card${pkg.popular ? ' donate-card--popular' : ''}">
      ${pkg.badge ? `<span class="donate-badge">${esc(pkg.badge)}</span>` : ''}
      <div class="donate-name">${esc(pkg.name)}</div>
      <div class="donate-wcoin">${pkg.wcoin.toLocaleString('es-AR')}</div>
      <div class="donate-wcoin-label">WCoin</div>
      <div class="donate-price">${esc(pkg.display_price)}</div>
      <div class="donate-cta">
        ${data.donation_url
          ? `<a href="${esc(data.donation_url)}" target="_blank" rel="noopener" class="btn btn-primary btn-full">Comprar</a>`
          : `<button class="btn btn-secondary btn-full" disabled>Próximamente</button>`
        }
      </div>
    </div>
  `).join('');
}
