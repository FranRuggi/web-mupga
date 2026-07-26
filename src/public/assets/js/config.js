/* ============================================================
   MuPGA — config.js
   URLs de backend. Se carga ANTES de app.js / landing.js.

   El HTML de producción es estático (Cloudflare Pages): PHP no corre y no
   inyecta nada, así que las URLs de producción van hardcodeadas acá. El
   patrón es siempre el mismo: mirar window.location.hostname, y si no es
   localhost usar la URL fija.

   Tres backends distintos:
     siteApi     → contenido del sitio (mupga_admin): servidores, noticias,
                   descargas, estado. Vive en el VPS principal.
     api         → API del SERVIDOR DE JUEGO de este hostname. Cada servidor
                   tiene su propia base de cuentas y su propio VPS.
     paymentsApi → VPS .NET de pagos.
   ============================================================ */

// Hostname → API del servidor de juego correspondiente.
// Al sumar un servidor: agregar acá su subdominio y su api_url (el mismo
// valor que va en mupga_admin.dbo.servidores.api_url).
const MUPGA_SERVER_APIS = {
  'servidor1.mupga.com.ar': 'https://api.mupga.com.ar',
  // 'servidor2.mupga.com.ar': 'https://api2.mupga.com.ar',
};

// API del servidor 1. Es también el default para hostnames no mapeados
// (previews de Cloudflare Pages: <rama>.web-mupga.pages.dev) y el host
// histórico mupga.com.ar mientras el DNS de la landing no esté migrado.
const MUPGA_DEFAULT_API = 'https://api.mupga.com.ar';

const MUPGA_CONFIG = {
  api: (function () {
    const host = window.location.hostname;
    if (host === 'localhost' || host === '127.0.0.1') {
      return ''; // desarrollo: URLs relativas, PHP inyecta el base
    }
    return MUPGA_SERVER_APIS[host] ?? MUPGA_DEFAULT_API;
  })(),

  // El contenido del sitio (incluida la tabla de servidores que alimenta la
  // landing) vive en mupga_admin, servida por el VPS principal — no cambia
  // según el servidor de juego que estés mirando.
  siteApi: (function () {
    const host = window.location.hostname;
    if (host === 'localhost' || host === '127.0.0.1') {
      return '';
    }
    return MUPGA_DEFAULT_API;
  })(),

  paymentsApi: (function () {
    if (window.location.hostname === 'localhost' ||
        window.location.hostname === '127.0.0.1') {
      const injected = (document.documentElement.dataset.paymentsUrl ?? '').replace(/\/$/, '');
      return injected || 'http://localhost:5000';
    }
    return 'https://donations.mupga.com.ar';
  })(),
};
