# CLAUDE.md — MuPGA Web
> Fuente de verdad para toda instancia de Claude Code en este repo.
> Leer completo antes de escribir una sola línea de código.
> Última actualización: Semana 1 — home + noticias deployados.

---

## Qué es este proyecto

Sitio web oficial del servidor privado MU Online **MuPGA** (mupga.com.ar).
Web tipo **vitrina + panel de jugador**, mobile-first, dark fantasy.
Público: jugadores hispanoablantes, mayoría accede desde **celular**.
Idioma: **español (Argentina)**. Tono: épico/fantasy, directo, sin pretensiones.

### Arquitectura
- **Este repo** → Frontend estático en **Cloudflare Pages** (HTML + CSS + JS vanilla)
- **API** → Backend Node.js + Express en el VPS (repo separado `mupga-api`, en construcción)
- **WebEngine (PHP)** → Sigue corriendo en el VPS para registro y panel de usuario hasta que la API esté lista

El dominio de desarrollo es **develop.mupga.com.ar** hasta que la web esté completa.
Cuando esté lista, se apunta **mupga.com.ar** a Cloudflare Pages.

---

## Stack — NO cambiar sin consultar

| Capa | Tecnología |
|---|---|
| Frontend | HTML5 + CSS3 + JS vanilla |
| Estilos | `css/mupga-design.css` (sistema de diseño propio) |
| Fuentes | Cinzel (títulos) + Roboto (cuerpo) — Google Fonts |
| Datos dinámicos temporales | JSON estático en `data/` |
| Efectos visuales | CSS puro — sin librerías externas de animación |
| Deploy | Cloudflare Pages — git push = deploy automático |

**Prohibido:**
- Instalar npm, webpack, vite, react, vue, o cualquier bundler
- Usar Bootstrap, Tailwind, o cualquier framework CSS externo
- Agregar librerías JS de animación (GSAP, Anime.js, etc.)
- Hardcodear colores — siempre `var(--nombre-variable)`
- Usar `!important` salvo casos extremos documentados
- Conectar directo a SQL Server desde el frontend

---

## Estructura del proyecto

```
mupga-web/
├── CLAUDE.md
├── index.html              ✅ Completo — revisar secciones inferiores
├── noticias.html           ✅ Completo — revisar diseño de cards
├── noticia.html            ⏳ Pendiente
├── info.html               ⏳ Pendiente — PRIORIDAD 2
├── ranking.html            ⏳ Pendiente
├── descargar.html          ⏳ Pendiente — PRIORIDAD 3
├── donaciones.html         ⏳ Pendiente — PRIORIDAD 4
├── css/
│   └── mupga-design.css    ✅ Sistema de diseño — NO TOCAR
├── js/
│   └── main.js             ✅ Completo — hamburger + active link
└── data/
    └── noticias.json       ✅ Existe — cargar noticias reales
```

---

## Sistema de diseño — Variables CSS

Siempre usar estas variables. Nunca hardcodear valores.

```css
/* Fondos */
--bg-void:      #0a0a0f    /* fondo de página */
--bg-deep:      #0f0f1a    /* navbar, footer */
--bg-surface:   #141420    /* secciones alternas */
--bg-raised:    #1c1c2e    /* elementos elevados */
--bg-card:      #1e1e30    /* cards */

/* Dorados */
--gold-bright:  #f5c842    /* títulos, íconos activos */
--gold-mid:     #c9952a    /* bordes, acentos, hover */
--gold-dark:    #7a5a1a    /* bordes sutiles */
--gold-dim:     #3d2d0d    /* background de tags */

/* Rojo */
--red-accent:   #c0392b    /* CTA principal, alertas */
--red-bright:   #e74c3c    /* hover del rojo */

/* Texto */
--text-primary:   #f0ead6
--text-secondary: #a89a7a
--text-muted:     #5c5448

/* Tipografía */
--font-display: 'Cinzel', serif       /* solo h1–h4, logo, títulos */
--font-body:    'Roboto', sans-serif  /* todo lo demás */
```

---

## Efectos visuales — reglas

El hero tiene fondo difuminado. Para agregar efectos visuales:

**Permitido (CSS puro, liviano):**
- Gradientes animados con `@keyframes` en fondos
- `backdrop-filter: blur()` para glassmorphism suave
- `text-shadow` dorado en títulos principales
- `box-shadow` con color `--gold-mid` en hover de cards
- Pseudo-elementos `::before` / `::after` para detalles decorativos
- `opacity` y `transform` transitions (GPU-accelerated, no afectan performance)

**Prohibido:**
- Canvas animations
- Librerías externas de partículas o animación
- Animaciones en `loop` infinito sobre elementos grandes (consume batería mobile)
- `filter` pesados en imágenes grandes

---

## Stats en tiempo real — patrón a seguir

Los stats del servidor (jugadores online, etc.) se conectarán a la API cuando esté lista.
**Por ahora usar este patrón con datos mock:**

```javascript
// En main.js o script inline de index.html
async function fetchServerStats() {
  try {
    const res = await fetch('https://api.mupga.com.ar/stats');
    const data = await res.json();
    document.getElementById('stat-online').textContent = data.playersOnline;
    document.getElementById('stat-accounts').textContent = data.totalAccounts;
  } catch (err) {
    // Si la API no responde, mostrar datos mock
    document.getElementById('stat-online').textContent = '—';
    document.getElementById('stat-accounts').textContent = '—';
  }
}
fetchServerStats();
```

**Stats a mostrar en el home:**
- Jugadores online ahora
- Total de cuentas registradas
- XP Rate (estático: 50x)
- Season (estático: Season 6)

El endpoint real será `https://api.mupga.com.ar/stats` — no inventar otra URL.

---

## Noticias — formato JSON

```json
[
  {
    "id": 1,
    "titulo": "Título de la noticia",
    "tag": "Actualización",
    "tag_tipo": "gold",
    "resumen": "Texto breve para la card (máx 120 caracteres).",
    "contenido": "Texto completo en HTML o texto plano.",
    "fecha": "2025-06-01",
    "autor": "Staff MuPGA"
  }
]
```

`tag_tipo`: `"gold"` (dorado) o `"red"` (eventos urgentes/mantenimiento).

---

## Lenguaje — reglas de contenido

El servidor es gratuito pero tiene beneficios opcionales financiados por donaciones.

**NUNCA usar:** vender, comprar, precio, producto, tienda, store, shop, pago, costo.
**SIEMPRE usar:** donar, donación, apoyar, beneficios para donadores, supporter, a cambio de tu donación recibís.

Ejemplos correctos:
- "Apoyá el servidor y recibí WCoins"
- "Pack Supporter — a cambio de tu donación"
- "Beneficios exclusivos para donadores"
- "Donación VIP — acceso a beneficios premium"

---

## Links a WebEngine — convención actual

```html
<!-- Registro -->
<a href="https://mupga.com.ar/register" class="btn btn-danger">Crear cuenta gratis</a>

<!-- Login / Panel -->
<a href="https://mupga.com.ar/user" class="btn btn-secondary">Mi cuenta</a>
```

Cuando la API propia esté lista, estos links se actualizan. No crear páginas de registro ni login en este repo todavía.

---

## Navbar — copiar exacta en todas las páginas

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
  <a href="https://mupga.com.ar/register" class="btn btn-danger navbar-cta">Registrarse</a>
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
  <a href="https://mupga.com.ar/register" class="btn btn-danger w-full">Registrarse</a>
</div>
```

El `.active` lo aplica `main.js` automáticamente según la URL actual.

---

## Estructura HTML base de cada página

```html
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MuPGA — [Nombre de la página]</title>
  <meta name="description" content="[descripción SEO]">
  <link rel="stylesheet" href="css/mupga-design.css">
</head>
<body>
  <nav class="navbar">...</nav>
  <div class="navbar-mobile-menu" id="mobileMenu">...</div>

  <main>...</main>

  <footer class="footer">...</footer>
  <script src="js/main.js"></script>
</body>
</html>
```

---

## Prioridades actuales — en este orden

1. **Mejorar `index.html`** — sección de stats con patrón fetch+mock, cards de noticias mejoradas, sección de clases disponibles, sección CTA donaciones
2. **Crear `info.html`** — rates del servidor, clases jugables, comandos disponibles, eventos
3. **Crear `descargar.html`** — link de descarga del cliente, requisitos, instrucciones de conexión al servidor
4. **Crear `donaciones.html`** — packs de WCoins y VIP con lenguaje de donación (nunca "comprar")
5. **Crear `noticia.html`** — detalle de noticia individual, leída desde noticias.json por ID en URL
6. **Mejorar `noticias.html`** — cards más elaboradas, filtro por tag
7. **Crear `ranking.html`** — tabla de ranking, por ahora datos mock o redirect a WebEngine

---

## Contexto del servidor MU

- **Season:** 6 completo
- **XP Rate:** 50x (confirmar con admin si cambió)
- **Modalidad:** Free to play — sin pay to win
- **Clases disponibles:** Dark Wizard, Dark Knight, Elf, Magic Gladiator, Dark Lord, Summoner, Rage Fighter (confirmar lista completa con admin)
- **Donaciones:** WCoins y VIP — nunca usar la palabra "venta" ni "comprar"
- **Staff:** activo, presente en Discord
- **Comunidad:** hispanoablante, Argentina principalmente