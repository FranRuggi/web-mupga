/* ============================================================
   MuPGA — config.js
   URL base de la API. Se carga ANTES de app.js.

   Escenario A (actual — VPS todo junto):
     PHP inyecta data-base-url en el HTML → app.js lo lee directamente.
     Este archivo no tiene efecto, pero debe estar presente.

   Escenario B (futuro — Cloudflare Pages + VPS separados):
     El HTML estático no tiene PHP, así que app.js no encuentra
     data-base-url y cae al fallback de MUPGA_CONFIG.api.
     Editar PROD_API_URL antes de hacer push para ese escenario.
   ============================================================ */

const MUPGA_CONFIG = {
  api: (function () {
    if (window.location.hostname === 'localhost' ||
        window.location.hostname === '127.0.0.1') {
      return ''; // desarrollo: URLs relativas, PHP inyecta el base
    }
    return 'https://api.mupga.com.ar'; // producción VPS
  })(),

  // URL base de la API externa de pagos.
  // Fuente de verdad: PAYMENTS_API_URL en .env del VPS.
  // build.php lo bake en data-payments-url del <html> al generar el dist/.
  // Si no está configurado, donate.js muestra "tienda no disponible".
  paymentsApi: (function () {
    const injected = document.documentElement.dataset.paymentsUrl ?? '';
    if (injected) return injected.replace(/\/$/, '');
    if (window.location.hostname === 'localhost' ||
        window.location.hostname === '127.0.0.1') {
      return 'http://localhost:5000'; // dev local sin .env
    }
    return ''; // sin .env configurado → tienda muestra "no disponible"
  })()
};
