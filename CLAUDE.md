# MuPGA Web — sitio custom

Sitio web propio para el servidor privado de MU Online Season 6 **MuPGA** (MuEmu Louis v31).
Reemplaza progresivamente a WebEngine con un sitio PHP a medida, sin las limitaciones del CMS.

Idioma de trabajo: **español (Rioplatense)**. Comentarios de código y mensajes de commit en español.

## Estado actual

**Leé `ROADMAP.md` siempre al iniciar sesión** — es la fuente de verdad del progreso.
Fase activa: **Fase 1 — Ingeniería inversa** (ver roadmap).

## Arquitectura

### Producción — dos capas separadas

```
Browser → Cloudflare Pages (frontend estático)
                    ↓ fetch() / authFetch()
         VPS Windows Server — Apache/XAMPP (API PHP)
                    ↓ PDO/sqlsrv
         SQL Server Express (mismo VPS)
```

- **Frontend:** HTML/CSS/JS estático desplegado en **Cloudflare Pages** vía GitHub Actions.
  PHP **no corre** en Cloudflare — el HTML es estático generado en el build.
- **API PHP:** corre en el VPS con **Apache (XAMPP en Windows Server)**. Sirve únicamente
  los endpoints bajo `/api/`. El `.env` del VPS solo afecta a la API, no al frontend.
- **Base de datos:** Microsoft SQL Server Express en el mismo VPS. Conexión local, nunca
  remota. Driver: `sqlsrv` / `PDO_SQLSRV`.
- **La conexión sitio → base de datos es local dentro del VPS**, nunca desde una máquina externa.

### Consecuencias importantes para el desarrollo

1. **PHP no inyecta nada en el HTML de producción.** Atributos como `data-payments-url`
   o `data-base-url` que el layout PHP genera solo funcionan en desarrollo local (XAMPP).
   En Cloudflare Pages el HTML es estático y esos atributos quedan vacíos o con el valor
   que tenían al momento del build.
2. **URLs de producción en JS van hardcodeadas en `config.js`.** No confiar en
   `data-payments-url` ni en ningún atributo PHP-inyectado para entornos de producción.
   El patrón correcto: verificar `window.location.hostname`, si no es localhost → URL fija.
3. **Cambiar el `.env` del VPS no afecta el frontend** (está en Cloudflare). Solo afecta
   a los endpoints PHP de la API.
4. **Para ver cambios en el frontend** hay que hacer push → GitHub Actions → Cloudflare
   despliega automáticamente.

### Desarrollo local (PC de Franco)

- XAMPP sirve el sitio completo (PHP genera el HTML dinámicamente).
- Base de datos espejo en SQL Server Express local (schema restaurado desde dump de producción).
- En local sí funciona la inyección PHP de `data-payments-url` (lo lee del `.env` local).

## Reglas duras (no negociables)

1. **Nunca** escribir en tablas de juego (Character, inventario, ítems, zen, stats) de un
   personaje conectado mientras el GameServer corre: el server mantiene el estado en memoria y
   pisa los cambios → corrupción/duplicación de datos. Antes de cualquier escritura, consultar
   `.claude/docs/capability-matrix.md`.
2. **Siempre** PDO/sqlsrv con **sentencias preparadas**. Nunca concatenar input del usuario en
   SQL. (Este proyecto tiene foco fuerte en anti-cheat y prevención de abuso.)
3. **No inventar el schema.** Toda estructura de DB sale de leer `htdocs/` (código WebEngine) y
   el dump `script.sql`. Si algo no está confirmado ahí, marcarlo como "a verificar".
4. `htdocs/` es **solo referencia / lectura**. No modificar WebEngine. El sitio nuevo vive en `src/`.
5. Nunca commitear credenciales ni datos de jugadores (ya cubierto en `.gitignore`).
   Credenciales del sitio → variables de entorno, jamás hardcodeadas.
6. **Nunca usar tablas `WEBENGINE_*`.** Esas tablas pertenecen al CMS reemplazado
   (WebEngine) y no existen en producción. Si una feature las necesita, implementar
   tabla propia o documentar el caso como pendiente.

## Flujo de trabajo (importante)

- **Al iniciar cada sesión:** leé `ROADMAP.md` para saber en qué fase y tarea estás.
- **Al completar cualquier tarea:** actualizá `ROADMAP.md` — marcá el ítem con `[x]`, agregá la
  fecha y una línea en "Registro de cambios" con lo que se hizo. El roadmap es un checklist vivo.
- **Antes de avanzar de fase:** la fase anterior tiene que estar completa y revisada por Franco.
- Mantené este `CLAUDE.md` corto. El detalle pesado va en `.claude/docs/` (se lee on-demand para
  no gastar tokens en cada sesión).

## Estructura del repo

- `CLAUDE.md` — este archivo (reglas del proyecto).
- `ROADMAP.md` — checklist vivo de fases y tareas.
- `.claude/skills/` — skills del proyecto (se crean en Fase 1).
- `.claude/docs/` — referencia pesada que leen los skills on-demand:
  - `data-dictionary.md` — tablas, columnas, stored procedures (se genera en Fase 1).
  - `capability-matrix.md` — qué es seguro / riesgoso / prohibido en la DB (Fase 1).
- `script.sql` — dump productivo en la raíz (gitignoreado, solo lectura local).
- `db/schema/` — exports de schema-only, versionables (opcional).
- `htdocs/` — código WebEngine actual (referencia, solo lectura).
- `src/` — el sitio custom nuevo.

## Módulo Prode

Página `/mudial/` donde jugadores logueados predicen resultados de partidos y ganan WCoins y días VIP
automáticamente al cargar cada resultado.

**Schema DB:** `prode` (separado de `dbo` para aislar el módulo y poder limpiarlo al terminar el mundial).
Tablas: `prode.config` (key/value), `prode.matches`, `prode.predictions`, `prode.scores`.

**Usuario SQL:** `prode_user`. Permisos: CONTROL en schema `prode`; SELECT en `dbo.ACCOUNT_TBL`,
`dbo.Character`, `dbo.MEMB_INFO`; EXECUTE en `dbo.sp_AddWCoinWithLog` y `dbo.sp_SetAccountGOLDVIP`.
Los SPs de premios se ejecutan a través de la conexión principal (`Database::get()`) para mayor seguridad.

**Archivos API:** `src/public/api/prode/` (matches, predict, ranking, admin_match, admin_result).
**Frontend:** `src/public/mudial/index.php` + `src/public/assets/js/mudial.js`.
**Conexión prode:** `src/config/prode_db.php` + `src/db/ProdeRepository.php`.

**Variables de entorno necesarias:** `PRODE_DB_HOST`, `PRODE_DB_PORT`, `PRODE_DB_NAME`,
`PRODE_DB_USER`, `PRODE_DB_PASS`, `ADMIN_TOKEN`.

**Regla:** nunca hardcodear valores de premios — siempre leer de `prode.config` con `getConfig()`.

**Endpoints admin** (`/api/prode/admin_*.php`): protegidos con header `X-Admin-Token: <ADMIN_TOKEN>`.

**Cierre del mundial:** ejecutar `DROP SCHEMA prode CASCADE`, `DROP USER prode_user`,
`DROP LOGIN prode_user`, borrar las variables `PRODE_*` del `.env` y eliminar
`/api/prode/` y `/mudial/` del repo.

## Módulo ControlPanel (migración en etapas — Fase 7 del roadmap)

Contenido del sitio (info del servidor, descargas, noticias, estado del sitio) migra de JSON
estáticos del repo a la base **`mupga_admin`** (separada de la del juego), editable a futuro
desde un panel propio.

**Login SQL:** `mupga_web_svc` — `db_datareader`+`db_datawriter` en `mupga_admin`, sin DDL.
Sobre la base del juego: **solo** SELECT en la view `dbo.vw_web_auth` (memb___id, memb__pwd).
Nunca tocar tablas del juego desde este módulo.

**Conexión:** `src/config/admin_db.php` (clase `AdminDatabase`, PDO separada). Env vars:
`ADMIN_DB_HOST/PORT/NAME/USER/PASSWORD`.

**Tablas** (`mupga_admin.dbo`): `admins`, `site_status` (fila única id=1), `status_presets`,
`news`, `server_info` (blob JSON en `config_key='secciones'`), `downloads`.

**Endpoints públicos:** `GET /api/site/server-info.php`, `GET /api/site/downloads.php`,
`GET /api/site/news.php`, `GET /api/site/status.php` (este último con `no-store` — canal de
emergencia). Columnas `updated_by`/`created_by` son nvarchar(10) — valores cortos.

**Panel de escritura:** `/controlpanel/` (página + `controlpanel.js`). Endpoints
`/api/admin/*.php` protegidos con `requireAdmin()` (`src/lib/AdminAuth.php`): token JWT del
sitio + `memb___id` en `dbo.admins` con `active=1`, si no 403. Mutaciones POST-only; el
Bearer token en header actúa como protección CSRF (no hay cookies de sesión). `site_status`
y `server_info` se escriben en transacción con `UPDLOCK/HOLDLOCK`. Modo de status: allowlist
estricta `banner`/`overlay`. El JSON de server_info se valida antes de guardar (nunca
guardar contenido roto).

**Regla de etapas:** implementar una etapa, avisar y ESPERAR confirmación de Franco antes
de la siguiente. Estado por etapa en `ROADMAP.md` (Fase 7).

## Incidentes de Seguridad

### 2026-06-19 — Bypass del cutoff de predicciones (Prode)

**Qué se descubrió:** un jugador registró 5 predicciones exactas consecutivas (3 puntos c/u) en
partidos del Mundial 2026. Los `submitted_at` en la DB mostraban entre 48 y 110 minutos antes del
inicio — al menos uno dentro de la ventana de 60 minutos prohibida por las reglas.

**Cómo se explotó:** la validación del cutoff de 60 minutos estaba implementada en PHP usando
`new DateTime('now')` en lugar de `GETDATE()` de SQL Server. Cualquier desfase entre el reloj del
servidor PHP y el del SQL Server (o la hora real) abría una ventana de aceptación más amplia que
la nominal. Más crítico: el campo `is_locked` solo se setea a `1` cuando el admin corre
`admin_result.php` — hasta ese momento, un partido arrancado pero no resuelto seguía aceptando
predicciones si el check de PHP lo permitía. Adicionalmente, el SELECT de validación y el MERGE de
inserción no estaban envueltos en una transacción, exponiendo una race condition TOCTOU. El campo
`submitted_at` tampoco se seteaba explícitamente en el path INSERT del MERGE, dependiendo de un
DEFAULT de schema que podía no existir.

**Causa raíz:** validación temporal en capa PHP en lugar de SQL Server; ausencia de lock temporal
automático al inicio del partido independiente del flag `is_locked`; no atomicidad entre lectura y
escritura.

**Qué se corrigió — fix inicial (2026-06-19):**
1. Cutoff reemplazado por query SQL Server con `DATEADD(MINUTE, 60, GETDATE()) < match_datetime_utc`.
2. `is_locked = 0` incluido en la query como condición temporal.
3. SELECT + MERGE envueltos en transacción con `WITH (UPDLOCK, HOLDLOCK)` — elimina TOCTOU.
4. `submitted_at = GETDATE()` explícito en el path INSERT del MERGE.

**Resolución final (2026-06-19):** fix iterado al constatar que SQL Server Express (edición del
VPS) no tiene SQL Server Agent, descartando cualquier enfoque basado en jobs programados. El
flag `is_locked` no puede actualizarse automáticamente y por tanto no puede ser el mecanismo
primario de cutoff.

Correcciones adicionales:
- `is_locked = 0` **eliminado** de la condición temporal. El cutoff por tiempo es la única
  fuente de verdad. `is_locked` queda como guarda secundaria (el admin puede cerrar un partido
  manualmente) y señal al frontend, pero nunca como enforcement temporal primario.
- Frontend (`mudial.js`): `isMatchOpen()` evalúa primero el cutoff por tiempo UTC del cliente y
  luego `is_locked` como guarda secundaria. El badge "SE JUEGA PRONTO" aparece en los últimos
  60 minutos antes del inicio, independiente del estado de predicciones.

**Anomalía de timezone en el VPS (2026-06-19):** `GETUTCDATE()` en este servidor devuelve
UTC-5 en lugar de UTC — comportamiento incorrecto, causa desconocida (posiblemente configuración
del SO o de la instancia SQL Server Express). `GETDATE()` devuelve hora de Argentina (UTC-3),
que es el comportamiento esperado según la zona horaria del VPS.

Workaround aplicado: `GETUTCDATE()` reemplazado por `DATEADD(HOUR, 3, GETDATE())` en toda
referencia de `savePrediction()` (condición de cutoff y `submitted_at` en ambos paths del MERGE).
`match_datetime_utc` almacena UTC real, por lo que `DATEADD(HOUR, 3, GETDATE())` es la expresión
correcta para obtener el instante UTC actual en este servidor.

**Regla permanente para este proyecto:** nunca usar `GETUTCDATE()` en queries de `prode.*`.
Usar siempre `DATEADD(HOUR, 3, GETDATE())` para obtener UTC real.

**Acción pendiente:** auditar la DB en busca de predicciones con `submitted_at` posterior a
`match_datetime_utc` o dentro de los 60 minutos previos al inicio; evaluar si corresponde
anular puntos otorgados por esas predicciones.

## Objetivo del producto

Con base en la matriz de capacidades, definir qué features se le pueden ofrecer al cliente de
forma segura (rankings, conteo de online, info de cuentas/personajes, registro, reset de
password, créditos de WCoin/CashShop, estado VIP) y dejar documentado qué NO se puede hacer
desde la web por riesgo de corromper datos del juego. Esa matriz decide si el sitio custom
reemplaza a WebEngine o no.
