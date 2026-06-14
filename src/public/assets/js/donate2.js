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
  }
});
