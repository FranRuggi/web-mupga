/* ============================================================
   MuPGA — donate2.js
   Página informativa de compra de WCoins.
   Depende de: app.js (BASE), auth.js (isAuthenticated)
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  if (!isAuthenticated()) {
    window.location.replace(
      BASE + '/login/?redirect=' + encodeURIComponent(window.location.pathname)
    );
    return;
  }

  document.querySelectorAll('[data-promo]').forEach(wrap => {
    const select = wrap.querySelector('.donate2-promo-select');
    const link   = wrap.querySelector('[data-promo-link]');
    if (!select || !link) return;
    select.addEventListener('change', () => { link.href = select.value; });
  });

  // Modales: QR de Binance y datos de transferencia. Cada uno se engancha por
  // su cuenta — antes había un `return` temprano si no existía el de Binance,
  // que habría dejado sin conectar cualquier modal agregado después.
  wireModal('binance-modal', '[data-open-qr]');
  wireModal('transfer-modal', '[data-open-transfer]');

  const qrImg = document.getElementById('binance-qr-img');
  if (qrImg) qrImg.src = BASE + '/assets/img/binance.jpeg';

  wireCopyAlias();
});

// ── Modal genérico ────────────────────────────────────────────
function wireModal(modalId, openSelector) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  const overlay  = modal.querySelector('.donate2-modal__overlay');
  const closeBtn = modal.querySelector('.donate2-modal__close');

  function openModal() {
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  document.querySelectorAll(openSelector).forEach(btn => btn.addEventListener('click', openModal));
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (overlay)  overlay.addEventListener('click', closeModal);
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });
}

// ── Copiar el alias de transferencia ──────────────────────────
function wireCopyAlias() {
  const btn = document.getElementById('transfer-copy-alias');
  const txt = document.getElementById('transfer-alias-text');
  const fb  = document.getElementById('transfer-copy-feedback');
  if (!btn || !txt) return;

  btn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(txt.textContent.trim());
    } catch {
      // Sin permiso de portapapeles (o navegador viejo): al menos se lo
      // dejamos seleccionado para copiar a mano.
      const rango = document.createRange();
      rango.selectNodeContents(txt);
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(rango);
    }
    if (fb) {
      fb.hidden = false;
      setTimeout(() => { fb.hidden = true; }, 2000);
    }
  });
}
