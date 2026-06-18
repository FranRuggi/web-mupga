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

  const modal    = document.getElementById('binance-modal');
  if (!modal) return;

  const qrImg = document.getElementById('binance-qr-img');
  if (qrImg) qrImg.src = BASE + '/assets/img/binance.jpeg';

  const overlay  = document.getElementById('binance-modal-overlay');
  const closeBtn = document.getElementById('binance-modal-close');
  const openBtns = document.querySelectorAll('[data-open-qr]');

  function openModal() {
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    closeBtn.focus();
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  openBtns.forEach(btn => btn.addEventListener('click', openModal));
  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', closeModal);
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });
});
