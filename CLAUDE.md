# CLAUDE.md — MuPGA Web
> Este archivo es la fuente de verdad para cualquier instancia de Claude Code trabajando en este repositorio.
> Leerlo completo antes de escribir una sola línea de código.

---

## Qué es este proyecto

Sitio web oficial del servidor privado MU Online **MuPGA** (mupga.com.ar).
Es una web de tipo **vitrina + panel de jugador**, mobile-first, dark fantasy.

El proyecto está dividido en dos partes que conviven:
- **Este repo** → Frontend estático desplegado en **Cloudflare Pages** (HTML + CSS + JS vanilla)
- **API separada** → Backend Node.js + Express en el VPS (repo: `mupga-api`, aún en construcción)

El CMS anterior (WebEngine, PHP) sigue corriendo en el VPS para registro y panel de usuario **hasta que la API esté lista**. No reemplazar esa funcionalidad en este repo todavía.

---

## Stack — NO cambiar sin consultar

| Capa | Tecnología | Motivo |
|---|---|---|
| Frontend | HTML5 + CSS3 + JS vanilla | Sin builds, sin dependencias, Cloudflare Pages lo sirve directo |
| Estilos | `css/mupga-design.css` (sistema de diseño propio) | Un solo archivo, variables CSS, sin frameworks |
| Fuentes | Cinzel (títulos) + Roboto (cuerpo) vía Google Fonts | Identidad visual aprobada |
| Datos dinámicos temporales | JSON estático en `data/` | Noticias editables sin tocar HTML |
| Deploy | Cloudflare Pages (git push = deploy automático) | CDN global, SSL automático |
| Dominio | mupga.com.ar | Apunta a Cloudflare Pages |

**No instalar npm, webpack, vite, react, vue ni ningún bundler en este repo.**
**No usar frameworks CSS (Bootstrap, Tailwind). Solo `mupga-design.css`.**

---

## Estructura del proyecto

```
mupga-web/
├── CLAUDE.md                  ← este archivo
├── index.html                 ← Home (página principal)
├── noticias.html              ← Lista de noticias
├── noticia.html               ← Detalle de noticia individual
├── info.html                  ← Info del servidor (rates, clases, comandos)
├── ranking.html               ← Rankings (redirige o iframe a WebEngine por ahora)
├── descargar.html             ← Descarga del cliente MU
├── cuenta.html                ← Panel de usuario (futuro — conecta a la API)
├── registro.html              ← Registro (redirige a WebEngine por ahora)
├── css/
│   └── mupga-design.css       ← Sistema de diseño — NO TOCAR sin consenso
├── js/
│   └── main.js                ← JS compartido (navbar, utilidades)
└── data/
    └── noticias.json          ← Noticias estáticas editables
```

---

## Sistema de diseño — Variables CSS

Todas las variables están en `css/mupga-design.css`. Usarlas siempre. Nunca hardcodear colores o fuentes.

```css
/* Fondos */
--bg-void      → #0a0a0f   (fondo de página)
--bg-deep      → #0f0f1a   (navbar, footer)
--bg-surface   → #141420   (secciones alternas)
--bg-raised    → #1c1c2e   (elementos elevados)
--bg-card      → #1e1e30   (cards)

/* Dorados */
--gold-bright  → #f5c842   (títulos, íconos activos)
--gold-mid     → #c9952a   (bordes, acentos, hover)
--gold-dark    → #7a5a1a   (bordes sutiles)
--gold-dim     → #3d2d0d   (background de tags)

/* Rojo */
--red-accent   → #c0392b   (CTA principal, alertas)
--red-bright   → #e74c3c   (hover del rojo)

/* Texto */
--text-primary   → #f0ead6  (texto principal)
--text-secondary → #a89a7a  (texto secundario)
--text-muted     → #5c5448  (labels, metadata)

/* Tipografía */
--font-display → 'Cinzel', serif      (h1–h6, logo, títulos de sección)
--font-body    → 'Roboto', sans-serif (todo el resto)
```

---

## Tipografía — reglas

- `font-family: var(--font-display)` → **solo** para h1–h4, logo, títulos de sección y elementos de impacto visual
- `font-family: var(--font-body)` → todo lo demás (párrafos, botones, labels, navegación)
- Botones: Roboto, uppercase, letter-spacing: 1.5px, font-size: 12px
- Labels de formulario: Roboto, uppercase, letter-spacing: 1px, font-size: 11px
- Nunca usar Arial, Inter, system-ui ni ninguna otra fuente

---

## Mobile-first — reglas obligatorias

1. **Diseñar primero para 375px** de ancho. Luego escalar con media queries.
2. Breakpoints disponibles en el design system:
   - `@media (min-width: 640px)` → tablet
   - `@media (min-width: 1024px)` → desktop
3. Tap targets mínimos: **44px × 44px** (botones, links de nav)
4. Nunca usar `hover` como única indicación de estado interactivo
5. Imágenes siempre con `max-width: 100%` y `display: block`
6. Texto mínimo: 13px. Nunca debajo de 12px

---

## Clases CSS disponibles — usar siempre estas, no inventar nuevas

### Layout
```
.container       → ancho máximo 1100px, centrado, padding lateral
.section         → padding vertical estándar de sección
.section-alt     → sección con fondo alternado (--bg-surface)
```

### Tipografía
```
.text-gold       → gradiente dorado (para títulos especiales)
.text-secondary  → color --text-secondary
.text-muted      → color --text-muted
.uppercase       → texto en mayúsculas con letter-spacing
.section-eyebrow → texto pequeño sobre el título de sección
```

### Componentes
```
.navbar / .navbar-logo / .navbar-links / .navbar-hamburger / .navbar-mobile-menu
.btn / .btn-primary / .btn-secondary / .btn-danger / .btn-ghost / .btn-lg / .btn-sm
.card / .card-subtle / .card-tag / .card-title / .card-body / .card-footer
.stat-pill / .stat-value / .stat-label / .stats-grid
.form-group / .form-label / .form-input / .form-error
.badge / .badge-online / .badge-offline
.divider
.footer / .footer-logo / .footer-links
.section-header
```

### Utilidades
```
.text-center / .text-right
.flex / .flex-center
.gap-sm / .gap-md
.mt-sm / .mt-md / .mt-lg / .mb-md / .mb-lg
.w-full / .hidden
```

---

## Estructura HTML de cada página

Cada página sigue esta estructura base:

```html
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MuPGA — [Nombre de la página]</title>
  <meta name="description" content="[descripción para SEO]">
  <link rel="stylesheet" href="css/mupga-design.css">
  <!-- favicon cuando esté disponible -->
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">...</nav>
  <div class="navbar-mobile-menu" id="mobileMenu">...</div>

  <!-- CONTENIDO PRINCIPAL -->
  <main>
    ...
  </main>

  <!-- FOOTER -->
  <footer class="footer">...</footer>

  <script src="js/main.js"></script>
</body>
</html>
```

---

## Navbar — estructura fija (copiar en todas las páginas)

```html
<nav class="navbar">
  <a href="index.html" class="navbar-logo">MuPGA</a>
  <div class="navbar-links">
    <a href="index.html">Inicio</a>
    <a href="noticias.html">Noticias</a>
    <a href="ranking.html">Ranking</a>
    <a href="info.html">Información</a>
    <a href="descargar.html">Descargar</a>
  </div>
  <a href="https://[URL-WEBENGINE]/register" class="btn btn-primary navbar-cta" target="_blank">Registrarse</a>
  <button class="navbar-hamburger" id="hamburgerBtn" aria-label="Menú">
    <span></span><span></span><span></span>
  </button>
</nav>

<div class="navbar-mobile-menu" id="mobileMenu">
  <a href="index.html">Inicio</a>
  <a href="noticias.html">Noticias</a>
  <a href="ranking.html">Ranking</a>
  <a href="info.html">Información</a>
  <a href="descargar.html">Descargar</a>
  <a href="https://[URL-WEBENGINE]/register" class="btn btn-primary w-full" target="_blank">Registrarse</a>
</div>
```

El link `.active` se agrega con JS según la página actual (ver `js/main.js`).

---

## Noticias — formato JSON

El archivo `data/noticias.json` tiene este formato. Editarlo directamente para agregar noticias:

```json
[
  {
    "id": 1,
    "titulo": "Título de la noticia",
    "tag": "Actualización",
    "tag_tipo": "gold",
    "resumen": "Texto breve para la card (máx 120 caracteres).",
    "contenido": "Texto completo de la noticia en HTML o texto plano.",
    "fecha": "2025-06-01",
    "autor": "Staff MuPGA"
  }
]
```

`tag_tipo` puede ser: `"gold"` (dorado, por defecto) o `"red"` (rojo, para eventos urgentes).

---

## Links a WebEngine — convención

Mientras el registro y el panel de usuario vivan en WebEngine, los links se escriben así:

```html
<!-- Registro -->
<a href="https://mupga.com.ar/register" class="btn btn-primary">Crear cuenta</a>

<!-- Login / Panel -->
<a href="https://mupga.com.ar/user" class="btn btn-secondary">Mi cuenta</a>
```

Cuando la API propia esté lista, estos links se actualizan a las nuevas rutas. **No crear páginas de registro ni login en este repo todavía.**

---

## Lo que NO hacer — reglas de seguridad del proyecto

1. **No tocar WebEngine** — los archivos PHP del WebEngine están en el VPS, no en este repo
2. **No instalar dependencias npm** en este repo
3. **No modificar `css/mupga-design.css`** sin justificación clara. Si algo del diseño no alcanza, agregar estilos en un `<style>` dentro de la página y documentarlo
4. **No hardcodear colores** — siempre `var(--nombre-variable)`
5. **No usar `!important`** salvo casos extremos y documentados
6. **No crear clases CSS con nombres genéricos** como `.box`, `.wrapper`, `.item` — usar las del sistema de diseño
7. **No agregar animaciones pesadas** — prioridad es performance en mobile
8. **No usar `position: fixed`** salvo para la navbar (ya implementada)
9. **No conectar directamente a SQL Server** desde el frontend — toda comunicación con la DB va por la API
10. **No subir credenciales, tokens ni contraseñas** al repo jamás

---

## Estado actual del proyecto

| Página | Estado |
|---|---|
| `css/mupga-design.css` | ✅ Completo |
| `js/main.js` | ⏳ Pendiente |
| `data/noticias.json` | ⏳ Pendiente |
| `index.html` (Home) | ⏳ Pendiente — **prioridad 1** |
| `noticias.html` | ⏳ Pendiente |
| `noticia.html` | ⏳ Pendiente |
| `info.html` | ⏳ Pendiente |
| `ranking.html` | ⏳ Pendiente |
| `descargar.html` | ⏳ Pendiente |

---

## Próximos pasos (en orden)

1. Crear `js/main.js` con hamburger menu y active link logic
2. Crear `data/noticias.json` con 2-3 noticias de ejemplo
3. Crear `index.html` — Home completo, mobile-first
4. Crear `noticias.html` — lista de noticias desde el JSON
5. Crear `info.html` — rates, clases, comandos del servidor
6. Crear `descargar.html` — descarga del cliente
7. Crear `ranking.html` — embed o redirect a WebEngine

---

## Contexto del negocio

- El servidor MU es **Season 6**, free to play
- Público objetivo: jugadores de MU Online hispanoablantes, mayormente **mobile**
- El tono es épico/fantasy pero sin ser pretencioso — amigable, directo
- Idioma del sitio: **español (Argentina)**
- Las noticias las escribe el admin (Franco) directamente en `noticias.json`
- Los jugadores se registran, logean y gestionan su cuenta en WebEngine por ahora
