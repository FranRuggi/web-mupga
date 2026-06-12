# Hero Slider — Documentación técnica

> Referencia on-demand. Leer antes de tocar el hero o el layout del home.

---

## Archivos involucrados

| Archivo | Rol |
|---|---|
| `src/public/index.php` | HTML del slider (lista de slides) |
| `src/public/assets/js/hero-slider.js` | Lógica vanilla JS del slider |
| `src/public/assets/css/main.css` | Estilos del hero y del layout |
| `src/public/assets/img/slider/` | Imágenes y videos del slider |

---

## Arquitectura del slider

### HTML (index.php)
```html
<section class="hero">
  <div class="hero-slider" aria-hidden="true">
    <div class="hero-slide"><video src="...mp4" muted playsinline preload="auto"></video></div>
    <div class="hero-slide"><img src="...jpg" alt=""></div>
    <!-- más slides... -->
  </div>
  <!-- orbs decorativos (position: absolute, z-index: 1) -->
  <div class="hero-content"> <!-- z-index: 2 --> </div>
</section>
```

El slider SIEMPRE se lee del DOM. No hay array hardcodeado en el JS.
Para agregar/quitar slides: editar solo el HTML de index.php.

### Z-index stack (de abajo hacia arriba)
```
.hero background (CSS gradient fallback)
.hero-slider / .hero-slide (z-index: 0)   ← imágenes y videos
.hero-orb x3 (z-index: 1)                 ← orbs decorativos
.hero-content (z-index: 2)                ← título, botones, links
```
El `.hero::after` (grid pattern) fue **eliminado** — tapaba el slider.

### CSS clave
```css
.hero-slider { position: absolute; inset: 0; z-index: 0; }
.hero-slide  { position: absolute; inset: 0; opacity: 0; transition: opacity 1s ease; }
.hero-slide.active { opacity: 1; }
.hero-slide img, .hero-slide video {
  width: 100%; height: 100%;
  object-fit: cover;
  object-position: center center;   /* NO usar "top" — corta el sujeto */
  filter: brightness(0.42) saturate(0.9);
}
```

**`object-position: center center`** es crítico. Si se cambia a `center top`:
- Imágenes landscape 16:9: recorta el fondo (la parte interesante es el centro)
- Causa que se vean "cortadas"

**`brightness(0.42)`**: suficientemente oscuro para legibilidad del texto,
suficientemente claro para que se vean los personajes. No bajar de 0.35.

### JS (hero-slider.js)
- Lee slides del DOM: `querySelectorAll('.hero-slide')`
- **Imágenes**: timer de 5 000 ms → siguiente
- **Videos**: espera evento `ended` → siguiente (sin `loop` attribute, sin audio)
- Fade crossfade: entra el nuevo slide (opacity 0→1) ANTES de que salga el anterior
- Duración del fade: 1 000 ms (CSS transition)
- Maneja race condition: si un video termina durante el fade, encola el avance

---

## Formato de imágenes

**Obligatorio: landscape 16:9** (ej. 1376×768 o 1920×1080)

- Imágenes portrait (vertical) en un hero landscape = recorte severo a pantallas anchas
- Todas las imágenes actuales son 1376×768 (16:9) salvo img-7 (1920×1080)
- Para IA generativa: pedir "1920×1080" o "16:9 landscape" explícitamente

### Videos
- `preload="auto"` solo en el primero (carga inmediata)
- `preload="metadata"` en el resto (ahorra ancho de banda en mobile)
- Atributos requeridos: `muted playsinline` (sin estos el autoplay falla en mobile)

---

## Layout del hero y el nav (bug resuelto)

### El problema
El `page-wrapper` usa CSS Grid con sidebar sticky:
```
header (sticky, top:0, z-index:200)
main | sidebar (sticky, top:68px, height: calc(100vh - 68px))
footer
```
Sin `grid-template-rows` explícito, algunos browsers calculan la altura de fila 2
basándose en el sidebar sticky → desplaza el inicio del hero o produce overlap con el nav.

### La solución
```css
/* Desktop (default) */
.page-wrapper {
  grid-template-rows: var(--header-h) 1fr auto;
}

/* Mobile (max-width: 1100px) — 4 filas porque sidebar baja */
.page-wrapper {
  grid-template-rows: var(--header-h) auto auto auto;
}
```
Esto ancla la fila del header a exactamente `68px` en todos los browsers.

### Comportamiento esperado
- **Scroll = 0**: nav en y=0–68, hero empieza en y=68. Sin overlap.
- **Al scrollear**: el hero desliza bajo el nav sticky (comportamiento CSS normal).
  El nav tiene `z-index: 200` dentro del stacking context de `page-wrapper (z-index:1)`.

---

## Alturas del hero

```css
/* Desktop base */
.hero { min-height: clamp(540px, 70vh, 860px); }

/* Monitores muy anchos (>1800px) */
@media (min-width: 1800px) {
  .hero { min-height: min(85vh, 1000px); }
}

/* Mobile */
@media (max-width: 768px) {
  .hero { min-height: 400px; padding: 2.5rem 1.5rem; }
}
```

En monitores >2000px de ancho, imágenes 1376×768 aún se recortan ~20%.
Es aceptable y esperado para `object-fit: cover` con contenedor de ratio 2.5:1+.

---

## Agregar slides nuevos

1. Copiar archivo a `src/public/assets/img/slider/` con nombre limpio (sin espacios, sin unicode)
2. Agregar línea en `index.php` dentro de `.hero-slider`:
   ```html
   <!-- imagen -->
   <div class="hero-slide"><img src="assets/img/slider/img-N.jpg" alt=""></div>
   <!-- video -->
   <div class="hero-slide"><video src="assets/img/slider/vid-N.mp4" muted playsinline preload="metadata"></video></div>
   ```
3. El JS lo recoge automáticamente. No tocar `hero-slider.js`.

## Quitar slides
Solo borrar la línea `<div class="hero-slide">...</div>` de index.php.
El JS se adapta solo.

---

## Convención de nombres en slider/

```
vid-1.mp4, vid-2.mp4, vid-3.mp4, vid-4.mp4   (videos)
img-1.jpg … img-10.jpg                        (imágenes, img-7 eliminada)
```
Al agregar nuevos: continuar la secuencia (img-11, vid-5, etc.).
