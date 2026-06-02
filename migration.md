# MuPGA Web — Guía de migración a producción

> **Arquitectura objetivo**
> - **Frontend** → Cloudflare Pages (HTML + CSS + JS estáticos, sin PHP)
> - **Backend** → VPS Windows con XAMPP (PHP API `/api/*.php` + SQL Server)
>
> El VPS ya tiene el GameServer y SQL Server corriendo.
> Cloudflare Pages no ejecuta PHP — los templates actuales deben convertirse a HTML puro.
> La única comunicación entre Pages y el VPS son los llamados `fetch()` de JS a la API.

---

## Estado actual vs. estado objetivo

| Capa | Local (ahora) | Producción (objetivo) |
|------|--------------|----------------------|
| HTML/CSS/JS | Generado por PHP + Apache (XAMPP local) | Archivos estáticos en Cloudflare Pages |
| API (`/api/*.php`) | Apache local | Apache en VPS (XAMPP) |
| Base de datos | SQL Server Express local (espejo) | SQL Server del VPS (producción) |
| Dominio | `localhost` | Dominio real (ej. `mupga.com`) |
| HTTPS | No | Sí (Cloudflare termina SSL) |

---

## Estado del Paso 1 (actualizado Fase 4 — 2026-06-01)

Los siguientes ítems del Paso 1 ya están **implementados** en el código:

| Ítem | Estado | Archivo |
|------|--------|---------|
| `_cors.php` creado | ✅ Hecho | `src/public/api/_cors.php` |
| CORS aplicado a nuevos endpoints auth y account | ✅ Hecho | `api/auth/*.php`, `api/account/*.php` |
| `TokenService.php` (auth tokens) | ✅ Hecho | `src/lib/TokenService.php` |
| `Auth.php` middleware | ✅ Hecho | `src/lib/Auth.php` |
| `APP_SECRET` en `.env.example` | ✅ Hecho | `.env.example` |
| `CORS_ALLOWED_ORIGINS` en `.env.example` | ✅ Hecho | `.env.example` |
| `DONATION_URL` en `.env.example` (único punto de config de pagos) | ✅ Hecho | `.env.example` |

**Pendiente del Paso 1:**
- [ ] Aplicar `_cors.php` a los endpoints existentes: `online.php`, `serverinfo.php`, `rankings.php`, `infodata.php`
- [ ] Crear `config.js` y actualizar `app.js` para la URL de API (necesario para Pages + VPS separados)
- [ ] Generar `dist/` con HTML estático de cada página

---

## Paso 1 — Cambios de código necesarios antes de migrar

Estos cambios se hacen en el repo local y se suben al repo de GitHub.

### 1.1 Agregar CORS a todos los endpoints de la API

Sin esto, Cloudflare Pages no podrá llamar a la API del VPS (blocked by CORS policy).

Crear `src/public/api/_cors.php` (helper interno, no accesible directo):

```php
<?php
// Incluir al inicio de cada api/*.php
// Reemplazar el origen con el dominio real de Cloudflare Pages
$allowedOrigins = [
    'https://mupga.pages.dev',    // dominio de Cloudflare Pages (temporal)
    'https://mupga.com',          // dominio custom cuando esté configurado
    'http://localhost',           // desarrollo local
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Accept, Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
```

Agregar al inicio de cada `api/*.php`:
```php
require_once __DIR__ . '/_cors.php';
```

Archivos a actualizar: `online.php`, `serverinfo.php`, `rankings.php`, `infodata.php`.

### 1.2 Crear `src/public/assets/js/config.js`

Actualmente el base URL de la API lo inyecta PHP via `data-base-url` en el HTML.
En producción el HTML es estático, así que la URL de la API va en un archivo JS de config.

```javascript
// src/public/assets/js/config.js
// Editar PROD_API_BASE antes de cada deploy
const MUPGA_CONFIG = {
  api: (function() {
    if (window.location.hostname === 'localhost') {
      return '';  // desarrollo: URLs relativas (API y frontend en el mismo origen)
    }
    return 'https://api.mupga.com';  // producción: URL absoluta del VPS
  })()
};
```

### 1.3 Actualizar `app.js` para leer `config.js`

Cambiar la línea de `BASE` en `app.js`:

```javascript
// Antes (PHP-injected):
const BASE = (document.documentElement.dataset.baseUrl || '/').replace(/\/$/, '');

// Después (config.js):
const BASE = (typeof MUPGA_CONFIG !== 'undefined' ? MUPGA_CONFIG.api : '');
```

Y actualizar `API`:
```javascript
const API = BASE ? `${BASE}/api` : '/api';
```

### 1.4 Convertir templates PHP a HTML estático

Los templates actuales usan PHP solo para el shell (URLs, año, título de página).
Hay que crear versiones HTML puras de cada página para Cloudflare Pages.

**Opción A — Manual (recomendada para este proyecto):**
Ejecutar cada página en local, copiar el HTML generado y guardarlo en `dist/`.

```bash
# Desde el directorio del proyecto (con Apache corriendo):
# Abrir en el browser, View Source, copiar a dist/
dist/
  index.html
  rankings/index.html
  info/index.html
```

En cada HTML estático, cambiar:
- `data-base-url="..."` → `data-base-url=""` (config.js lo maneja)
- `© 2025` → actualizar el año cuando sea necesario

**Opción B — Script de build (futuro):**
Un script PHP CLI que genera los HTML corriendo los templates server-side y guarda los archivos en `dist/`. Documentar como tarea futura si el sitio crece.

### 1.5 Agregar `config.js` al HTML estático

En cada HTML de `dist/`, agregar **antes** de `app.js`:
```html
<script src="/assets/js/config.js"></script>
<script src="/assets/js/app.js" defer></script>
```

---

## Paso 2 — VPS: setup del backend (API + DB)

El VPS ya tiene SQL Server y el GameServer. Solo hay que agregar la parte web.

### 2.1 Instalar XAMPP en el VPS

- Descargar XAMPP para Windows desde `https://www.apachefriends.org/`
- Instalar con Apache y PHP (no necesario MySQL ni MariaDB — ya tenemos SQL Server)
- Verificar que Apache no colisione con IIS si estuviera instalado (deshabilitar IIS o cambiar puertos)

### 2.2 Instalar extensión PHP para SQL Server

- Verificar la versión de PHP de XAMPP: `php --version`
- Descargar `php_sqlsrv_NN_ts_x64.dll` y `php_pdo_sqlsrv_NN_ts_x64.dll` desde:
  `https://github.com/microsoft/msphpsql/releases`
- Copiar los `.dll` a `C:\xampp\php\ext\`
- Agregar en `C:\xampp\php\php.ini`:
  ```ini
  extension=php_sqlsrv.dll
  extension=php_pdo_sqlsrv.dll
  ```
- Reiniciar Apache

### 2.3 Configurar el VirtualHost de Apache

Editar `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName api.mupga.com
    DocumentRoot "C:/ruta/al/repo/web-mupga/src/public"

    <Directory "C:/ruta/al/repo/web-mupga/src/public">
        AllowOverride All
        Require all granted
    </Directory>

    # Solo exponer el directorio /api/ externamente
    # (el resto del src/ no necesita ser accesible desde afuera)
</VirtualHost>
```

**Nota de seguridad:** considerar restringir el acceso a la raíz de `src/public` y solo exponer `/api/` externamente. El HTML estático lo sirve Cloudflare Pages, no el VPS.

### 2.4 Crear el `.env` en el VPS

En `C:/ruta/al/repo/web-mupga/.env`:
```
DB_HOST=localhost\SQLEXPRESS01   (o el nombre real de la instancia en el VPS)
DB_PORT=                         (vacío si SQL Server Browser corre)
DB_NAME=MuOnline
DB_USER=sa
DB_PASS=<contraseña real>
DB_USE_MD5=true
RANKINGS_LIMIT=25
APP_ENV=production
APP_BASE_URL=https://api.mupga.com/
```

**Nunca commitear este archivo.** Está en `.gitignore`.

### 2.5 Configurar HTTPS en el VPS (certificado SSL)

Opciones:
- **Cloudflare como terminador SSL (recomendado):** Cloudflare hace HTTPS entre el browser y sus servidores, y se conecta al VPS por HTTP. Más simple, no requiere cert en el VPS.
- **Certificado propio en el VPS:** instalar Let's Encrypt con Certbot para Windows y configurar HTTPS en Apache.

Si se usa Cloudflare como terminador, el VPS solo necesita HTTP interno (puerto 80) pero Cloudflare sigue sirviendo HTTPS al browser.

### 2.6 Verificar conectividad de la API

Desde un browser o Postman, verificar que los endpoints respondan:
```
https://api.mupga.com/api/online.php       → {"count": N}
https://api.mupga.com/api/serverinfo.php   → {...}
https://api.mupga.com/api/rankings.php?type=resets&limit=3 → [...]
https://api.mupga.com/api/infodata.php     → {...secciones...}
```

---

## Paso 3 — Cloudflare Pages: setup del frontend

### 3.1 Preparar el `dist/` con los HTML estáticos

Completar el Paso 1.4 (conversión de templates a HTML).
La carpeta `dist/` debe contener:
```
dist/
  index.html
  rankings/
    index.html
  info/
    index.html
  assets/
    css/main.css
    js/config.js
    js/app.js
    js/rankings.js
    js/info.js
    img/class/*.jpg
```

### 3.2 Conectar el repo a Cloudflare Pages

1. Ir a `https://dash.cloudflare.com/` → **Pages** → **Create a project**
2. Conectar el repositorio de GitHub `web-mupga`
3. Configurar el build:
   - **Framework preset:** None (sitio estático)
   - **Build command:** (vacío por ahora — no hay build step automatizado)
   - **Output directory:** `dist`
4. Deploy

Cloudflare Pages va a servir el contenido de `dist/` automáticamente en cada push al repo.

### 3.3 Configurar dominio custom

1. En Cloudflare Pages → **Custom domains** → agregar `mupga.com`
2. Agregar registros DNS en Cloudflare:
   - `mupga.com` → Cloudflare Pages (CNAME a `mupga.pages.dev`)
   - `api.mupga.com` → IP del VPS (registro A, con proxy **desactivado** si el VPS necesita la IP real, o activado si Cloudflare actúa de intermediario)

---

## Checklist de migración

### Código (pendiente antes de migrar)
- [x] Crear `src/public/api/_cors.php` — ✅ 2026-06-01
- [x] `APP_SECRET`, `CORS_ALLOWED_ORIGINS`, `DONATION_URL` en `.env.example` — ✅ 2026-06-01
- [ ] Aplicar `_cors.php` a endpoints existentes (online, serverinfo, rankings, infodata)
- [ ] Crear `src/public/assets/js/config.js` con la URL del VPS
- [ ] Actualizar `app.js` para usar `config.js` en lugar de `data-base-url` (compatibilidad Pages separado)
- [ ] Generar `dist/` con los HTML estáticos de cada página
- [ ] Agregar `config.js` en cada HTML de `dist/`
- [ ] Agregar `data/info.json` con valores reales (reemplazar PLACEHOLDERs)
- [ ] Generar `APP_SECRET` real en el VPS: `php -r "echo bin2hex(random_bytes(32));"`
- [ ] Configurar `DONATION_URL` en el `.env` del VPS cuando la plataforma de pagos esté lista

### VPS
- [ ] XAMPP instalado y Apache corriendo
- [ ] Extensión pdo_sqlsrv instalada y verificada
- [ ] VirtualHost configurado apuntando a `src/public`
- [ ] `.env` de producción creado en el VPS
- [ ] Verificar conectividad de API desde browser externo
- [ ] HTTPS configurado (vía Cloudflare o certificado propio)

### Cloudflare Pages
- [ ] Repo conectado a Cloudflare Pages
- [ ] Primer deploy exitoso (build de `dist/`)
- [ ] Dominio custom configurado (`mupga.com`)
- [ ] Subdominio API configurado (`api.mupga.com`)
- [ ] Verificar que los `fetch()` del frontend lleguen al VPS correctamente
- [ ] Verificar CORS: ningún error en consola del browser

---

## Notas adicionales

- **`data/info.json`** — editar directamente en el repo local. Al hacer push, Cloudflare Pages vuelve a deployar el frontend que al cargar pide el JSON al VPS. No hay paso extra.
- **Rankings** — consultan la DB de producción en tiempo real. Si el GameServer modifica datos durante el dia, los rankings se actualizan solos (el interval de 2 min en JS lo maneja).
- **Credenciales** — nunca van al repo. El `.env` del VPS se configura manualmente una vez.
- **Rollback** — si algo sale mal, Cloudflare Pages permite volver a un deploy anterior con un click.
