# Pantalla de apertura — operación

Cuenta regresiva a pantalla completa que tapa todo el sitio hasta la hora de apertura,
dejando como única acción el botón **REGISTRATE**.

**Hora objetivo actual:** `2026-09-05T00:00:00Z` = **viernes 04/09/2026, 21:00 hora de Argentina**.

---

## Lo importante en dos líneas

- **Se apaga sola** al llegar la hora. No hay que hacer nada a las 21:00.
- Para bajarla **antes** de hora hacen falta **dos pasos** (frontend y API van por caminos
  distintos): push a `main` **y** `git pull` en el VPS.

---

## Cómo está armada

| Capa | Archivo | Qué hace |
|---|---|---|
| Config (fuente única) | `src/config/apertura.php` | Interruptor + hora objetivo en UTC |
| Pantalla | `src/public/assets/js/apertura.js` + CSS `.apertura-*` en `main.css` | Contador, botón, guardián anti-DevTools |
| Bloqueo real | `src/lib/AperturaGate.php` (enganchado en `api/_cors.php`) | 503 en toda la API salvo lo exento |
| Reloj del server | `src/public/api/site/hora.php` | Sincroniza el contador (epoch UTC) |

**Páginas que NO se tapan:** `/register/`, `/login/`, `/controlpanel/` — ahí se muestra una
franja con el mismo contador. Si se taparan, el botón REGISTRATE no llevaría a ningún lado y
no se podría entrar al panel a arreglar nada.

**Endpoints que siguen respondiendo:** `auth/register.php`, `auth/login.php`,
`site/status.php`, `site/hora.php` y todo `/api/admin/*`.

---

## Bajarla antes de hora

1. En `src/config/apertura.php`:

   ```php
   const APERTURA_ACTIVA = false;
   ```

2. **Frontend (saca la pantalla):** commit + push a `main`. GitHub Actions rebuildea y
   Cloudflare Pages publica en ~1-2 min. Verificar con Ctrl+F5 en `https://mupga.com.ar`.

3. **API (saca el 503):** en el VPS, `git pull` en `C:\mupga` (no hace falta reiniciar Apache;
   PHP lee el archivo en cada request).

> Hacer solo el paso 2 deja el sitio visible pero con la API caída (503) → páginas vacías.
> Hacer solo el paso 3 abre la API pero la pantalla sigue tapando. **Van los dos.**

---

## Cambiar la hora de apertura

Editar `APERTURA_OBJETIVO_UTC` en `src/config/apertura.php` — siempre en **UTC con Z**
(`2026-09-05T00:00:00Z`). Para pasar de hora argentina a UTC: sumar 3 horas.
Después, los mismos pasos 2 y 3 de arriba.

**No** poner la hora local ni un `DATEADD(HOUR, N, GETDATE())`: la timezone del SO del VPS ya
cambió dos veces y rompió el prode (ver "Incidentes de Seguridad" en `CLAUDE.md`). El objetivo
en UTC absoluto es lo que hace que el contador dé bien en cualquier país.

---

## Volver a usarla en otra apertura

Poner `APERTURA_ACTIVA = true` y la nueva fecha objetivo. Nada más — no hay tablas ni SQL
que correr. Si el key art de fondo cambia, se actualiza la ruta en `apertura.js`
(`assets/img/slider/hero-apertura.jpg`).

---

## Si algo sale mal

| Síntoma | Causa probable |
|---|---|
| No aparece la pantalla | `APERTURA_ACTIVA = false`, la hora ya pasó, o el build de Cloudflare no corrió todavía |
| Aparece la pantalla pero el registro tira error | El VPS no tiene el `git pull`: `AperturaGate.php` no existe ahí y el 503 lo tira otra cosa (revisar Lockdown/mantenimiento en el ControlPanel) |
| El sitio queda tapado después de la hora | Reloj del visitante muy atrasado. Se corrige solo con `site/hora.php`; si esa URL da error, revisar que esté exenta en `Lockdown.php` y `AperturaGate.php` |
| Contador desfasado para todos | Correr en el VPS `SELECT GETDATE(), GETUTCDATE(), SYSDATETIMEOFFSET();` y comparar con la hora real (diagnóstico estándar del proyecto) |
