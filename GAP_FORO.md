# GAP_FORO.md — Auditoría de `BACKLOG_FORO.md` contra el repo real

> ⚠️ **Documento histórico**: refleja el estado ANTES del hardening v2 (mismo día,
> Etapas 1-3 del backlog — ver ROADMAP.md, Fase 8, "Hardening v2"). Se conserva como
> registro de la auditoría de Etapa 0; el estado real actual está en el ROADMAP.
>
> Generado en Etapa 0 (auditoría, sin código). Verificado contra el estado de `main` al
> 2026-08-11 (incluye los 2 fixes post-deploy: `ForumRepository` sin `require` y el cast de
> columnas `BIT` de SQL Server que llegaban como string truthy en JS).
>
> Convención de esta auditoría: **IMPLEMENTADA** = criterios cumplidos tal cual están escritos ·
> **PARCIAL** = existe una versión más simple/distinta · **AUSENTE** = no existe nada.
> Donde el backlog dice "moderador" se lee "admin" (no hay rol separado — ver épica F-08).

---

## Épica F-01 — Integridad del contenido y renderizado

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-01.01 Validación server-side | `[NUEVO]` | **PARCIAL** | Sí valida vacío y longitud máxima: `create_thread.php:32-37` (título ≤200, cuerpo ≤5000), `reply.php:26-28` (cuerpo ≤5000), `edit_post.php:31-33,40-42`. Falta: status 422 (usa 400), límites distintos por tipo (backlog pide título 120 / hilo 10.000 / respuesta 5.000, acá es 200/5000/5000 uniforme), normalización de saltos de línea repetidos, limpieza de caracteres de control/zero-width, y una constante/validador compartido — hoy los límites están **repetidos en 3 archivos** (`create_thread.php`, `reply.php`, `edit_post.php`), no centralizados. |
| F-01.02 Escape defensivo en la API | `[NUEVO]` | **AUSENTE, confirmado** | La API devuelve el `body` crudo tal cual está en la base (`thread.php`, `threads.php`, `categories.php` — sin ningún `esc()`/transformación antes de `json_encode`). El contrato real hoy es "cliente escapa" (`renderRichText()` en `app.js:46-68` llama `esc()` primero). No está documentado como contrato en ningún lado del código — solo se puede inferir. El whitelist de esquema de links (`https?://` únicamente) hoy vive **solo** en la regex del cliente (`app.js:52-53`) — no hay ningún filtro server-side sobre URLs. `javascript:`/`data:` no generan link hoy (la regex del cliente no matchea sin `http`), pero eso es incidental al único consumidor actual, no una garantía server-side. |
| F-01.03 Renderizador server-side | `[NUEVO]` | **AUSENTE, confirmado** | No existe ninguna función PHP equivalente a `renderRichText()`. Buscar en `src/` no arroja nada. |
| F-01.04 Vista previa | `[NUEVO]` | **AUSENTE, confirmado** | Los formularios de crear hilo (`foro/categoria/index.php`) y responder (`foro/hilo/index.php`) son `<textarea>` planos, sin botón ni panel de preview. (Sí existe un preview en el editor de Noticias del ControlPanel — `controlpanel/index.php`, `news-preview-toggle` — pero es admin-only para noticias, no aplica al foro público.) |

---

## Épica F-02 — Autoría de hilos

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-02.01 Crear hilo | `[YA]` | **PARCIAL** — ver discrepancia | Crear + redirect a permalink: `create_thread.php` + `foro.js:157-187` (`onCrearHilo`) ✔. El botón "Nuevo hilo" **no** está condicionado a `isAuthenticated()` (`foro.js:119-126`) — se muestra siempre que la categoría lo permita, y clickear redirige a login. Pero **no conserva el texto escrito al volver del login** (no hay nada de eso implementado — el criterio asume un flujo de "empecé a escribir y me mandan a loguear" que hoy no existe como tal). Ver discrepancia abajo. |
| F-02.02 Editar hilo con ventana de tiempo | `[PARCIAL]` | **PARCIAL, confirmado** | Editar existe (`edit_post.php:38-51`, dueño o admin, sin límite de tiempo). Falta: ventana de 30 min, label "editado hace X" (hoy `foro.js:226` solo pone el string fijo `"· editado"`), distinción "editado por un moderador" vs autoedición (hoy es el mismo texto en ambos casos), y la tabla de auditoría con el cuerpo anterior (no existe, ver F-08.05). |
| F-02.03 Borrar hilo (soft delete) | `[PARCIAL]` | **PARCIAL, pero el mecanismo es otro** | `ForumRepository::deleteThread()` (`ForumRepository.php:108-113`) hace `DELETE` **físico**, con `ON DELETE CASCADE` sobre `forum.posts`. No hay `deleted_at`/`deleted_by` en el schema (`foro_setup.sql`, tabla `forum.threads` no tiene esas columnas). No hay restricción "no puedo borrar si ya tiene respuestas de terceros" — dueño o admin borran siempre, sin importar respuestas. No hay papelera ni restore. El único criterio que sí se cumple es "permalink viejo da 404, no 500" (`thread.php:29-33`, incidental al hard-delete: `getThread()` devuelve null → 404). Pasar a soft-delete real es una migración de schema, no un ajuste chico. |
| F-02.04 Borrador autoguardado | `[NUEVO]` | **AUSENTE, confirmado** | Nada de `localStorage` para drafts en `foro.js`. |
| F-02.05 Prefijos por categoría | `[NUEVO]` | **AUSENTE, confirmado** | No existe columna de prefijos ni en `forum.categories` ni en `forum.threads`. |

---

## Épica F-03 — Respuestas

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-03.01 Responder | `[YA]` | **IMPLEMENTADA** | `reply.php` + `foro.js:350-390` (`initRespuestaForm`). `touchThreadActivity()` (`ForumRepository.php:127-134`) actualiza `reply_count` y `last_post_at` en cada post/delete. ✔ |
| F-03.02 Citar un mensaje | `[NUEVO]` | **AUSENTE, confirmado** | Sin botón "Citar", sin sintaxis `>` en el markdown-lite (`app.js` no la tiene). |
| F-03.03 Mencionar (@usuario) | `[NUEVO]` | **AUSENTE, confirmado** | Depende de infraestructura de notificaciones (épica F-07) que tampoco existe. |
| F-03.04 Editar/borrar respuesta propia | `[PARCIAL]` | **PARCIAL, confirmado** | `edit_post.php`/`delete_post.php` soportan `target_type: "post"` (dueño o admin). `deletePost()` (`ForumRepository.php:174-183`) es hard-delete — no hay placeholder "[mensaje eliminado por el autor]", el post desaparece del todo sea o no la última respuesta. |
| F-03.05 Permalink + paginación de respuestas | `[PARCIAL]` | **Más cerca de AUSENTE que de PARCIAL** | `getPostsByThread()` (`ForumRepository.php:140-146`) trae **todos** los posts sin `LIMIT`/página. No hay `id="post-{id}"` en el HTML (`foro.js:220-236`, `renderMensaje()` solo pone `data-id`, no `id`) — un link `#post-123` no scrollea a nada. El único criterio que "funciona" (editor visible sin navegar) es trivial porque no hay paginación que navegar. |
| F-03.06 Ir al primer no leído | `[NUEVO]` | **AUSENTE, confirmado** | No existe `forum.thread_reads` en `foro_setup.sql` ni en ningún lado. |

---

## Épica F-04 — Formato del mensaje

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-04.01 Barra de formato | `[NUEVO]` | **AUSENTE en el foro, confirmado** | Los `<textarea>` del foro no tienen toolbar. (Sí existe una completa en Noticias del ControlPanel — `controlpanel/index.php:144-158`, `.cp-editor__toolbar` — reusable como referencia de patrón, pero no está conectada al foro). |
| F-04.02 Bloque de código | `[NUEVO]` | **AUSENTE, confirmado** | `renderRichText()` no tiene manejo de triple backtick. |
| F-04.03 Spoiler | `[NUEVO]` | **AUSENTE, confirmado** | Nada. |
| F-04.04 Embed de YouTube | `[NUEVO]` | **AUSENTE, confirmado** | Nada. |
| F-04.05 Imágenes en posts | `[NUEVO]` | **AUSENTE, confirmado** | Cero upload en el foro. El patrón a portar (`upload_url.php`/`finalize.php` con presigned URLs a R2) existe y está probado en `src/public/api/reclamos/` — ver `reclamos.js:433-458` (`uploadImagen`) — pero no está conectado al foro ni hay bucket propio. |

---

## Épica F-05 — Reacciones

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-05.01 Agradecer | `[YA]` | **IMPLEMENTADA** | `react.php` + `ForumRepository::toggleReaction()` (`ForumRepository.php:190-213`) + `foro.js:33-58`. Redirect a login si no hay sesión: `foro.js:34-37`. ✔ |
| F-05.02 No autoagradecerse | `[NUEVO]` | **AUSENTE, confirmado** | `react.php:36-41` no compara `$auth['usr']` contra el autor del target — hoy **se puede** reaccionar al propio post/hilo. |
| F-05.03 Ver quiénes agradecieron | `[NUEVO]` | **AUSENTE, confirmado** | `getReactionCounts()` solo agrega (`COUNT`), no hay ningún método que devuelva la lista de cuentas. |
| F-05.04 Set ampliado de reacciones | `[NUEVO]` | **AUSENTE, confirmado** | `forum.reactions` no tiene columna `reaction_type` (`foro_setup.sql:105-117`). Migración aditiva viable tal como lo nota el backlog. |

---

## Épica F-06 — Identidad del autor

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-06.01 Personaje desactualizado | `[PARCIAL]` | **PARCIAL, confirmado** | `author_display_name` se resuelve y guarda una sola vez al postear (`create_thread.php:57-58`, `reply.php:48-49`, vía `CharacterRepository::getMainCharacterName()`). No hay job de refresco ni acción manual "actualizar mi identidad" — el nombre queda congelado para siempre tal como se resolvió la primera vez. |
| F-06.02 Perfil público | `[NUEVO]` | **AUSENTE, confirmado** | No hay página de perfil ni query de agregados (posts totales, agradecimientos recibidos) en `ForumRepository`. Punto a favor: hoy la UI del foro **nunca** expone `author_account` (solo `author_display_name`) — buena base para cuando se construya, ya cumple la restricción de privacidad del criterio de antemano. |
| F-06.03 Distintivos (staff/VIP/guild) | `[NUEVO]` | **AUSENTE en el foro, pero las piezas ya existen sueltas** | No hay badges en `foro.js`. Las fuentes de datos SÍ existen en el repo (`isAdminAccount()` en `AdminAuth.php`, estado VIP en `AccountRepository`, `GuildMember` en la base de juego) — es de "cablear", no de construir desde cero. |
| F-06.04 Firma | `[NUEVO]` | **AUSENTE, confirmado** | No hay columna `signature` en ninguna tabla. |

---

## Épica F-07 — Notificaciones

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-07.01 Seguir un hilo | `[NUEVO]` | **AUSENTE, confirmado** | No hay tabla de follows. |
| F-07.02 Centro de notificaciones | `[NUEVO]` | **AUSENTE, confirmado** | Sin campanita, sin tabla de notificaciones. |
| F-07.03 Aviso a Discord | `[NUEVO]` | **AUSENTE en el foro, patrón ya probado en Reclamos** | `create_thread.php` no llama a ningún webhook. El patrón (`DISCORD_WEBHOOK_RECLAMOS` + try/catch independiente) existe en el módulo de Reclamos y es directamente portable. |

---

## Épica F-08 — Moderación

> Nota de numeración: el documento pegado por Franco salta de **F-08** (intro) a **F-08.02** —
> no hay F-08.01 en el backlog recibido. No es un problema del código, es un hueco del propio
> documento — lo marco para que no se pierda si en algún momento se completa la numeración.

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-08.02 Reportar contenido | `[NUEVO]` | **AUSENTE, confirmado** | Sin botón "Reportar", sin tabla `forum.reports`. |
| F-08.03 Cola de reportes | `[NUEVO]` | **AUSENTE, confirmado** | Depende de F-08.02. |
| F-08.04 Acciones sobre hilos (pin/lock/mover) | `[PARCIAL]` | **PARCIAL, con un bug funcional vs. lo declarado** | Pin ✔ (`forum_moderate.php:38-39`, badge en `foro.js:147`). Lock ✔ (`forum_moderate.php:40-41`) **pero `reply.php:44-46` bloquea a TODOS sin excepción, admin incluido** — el criterio pide explícitamente "nadie puede responder, moderadores sí" y hoy el admin tampoco puede (tendría que desbloquear primero). "Mover a otra categoría": no existe ninguna acción ni método para eso. "Aviso visible con motivo" (ej. "Cerrado por el staff — motivo"): no existe — el lock no captura motivo en ningún lado, solo se ve el badge genérico "🔒 Cerrado". |
| F-08.05 Log de auditoría | `[NUEVO]` | **AUSENTE, confirmado — riesgo real** | No hay ninguna tabla de auditoría en `foro_setup.sql`. Hoy: editar/borrar/pin/lock no dejan ningún rastro de quién lo hizo ni cuándo (la única excepción es `forum.banned_accounts`, que sí guarda `banned_by`/`reason`/`banned_at` — pero solo para bans, nada más). |
| F-08.06 Ban con motivo y vencimiento | `[PARCIAL]` | **PARCIAL, confirmado** | `forum.banned_accounts` (`foro_setup.sql:121-131`) tiene `reason`, `banned_by`, `banned_at` — no tiene ninguna columna de expiración. Los bans son permanentes hasta unban manual (`forum_ban.php`, acción `unban`). Confirmado: nunca toca `MEMB_INFO.bloc_code` (`banAccount()`/`unbanAccount()` solo tocan `forum.banned_accounts`) — el usuario baneado del foro sigue jugando y usando el resto del sitio normal. Posts anteriores quedan visibles (el ban no oculta nada retroactivo). |

---

## Épica F-09 — Antiabuso

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-09.01 Límite de publicación (flood) | `[NUEVO]` | **AUSENTE, confirmado — riesgo real, P0 justificado** | `create_thread.php`, `reply.php`, y también `react.php`/`edit_post.php`/`delete_post.php` no tienen **ningún** rate-limit. Contraste directo: el módulo de Reclamos SÍ tiene esto resuelto y documentado (`reclamos/create.php:47-84`, cooldown 5 min por IP con `WITH (UPDLOCK, HOLDLOCK)`; `reclamos/reply.php:53-67`, cooldown 30s) — es el mismo patrón, listo para portar, no hay que inventarlo. |
| F-09.02 Requiere personaje para postear | `[NUEVO]` | **AUSENTE, confirmado — y con un efecto secundario** | `create_thread.php:57-58` hace `getMainCharacterName() ?? $auth['usr']` — si la cuenta no tiene personajes, **igual puede postear**, mostrando su cuenta de login como nombre público. Esto además roza F-06.02 (que pide nunca exponer `author_account`): hoy una cuenta sin personaje sí lo expone. |
| F-09.03 Links restringidos cuentas nuevas | `[NUEVO]` | **AUSENTE, confirmado** | No hay conteo de posts por cuenta ni whitelist de dominios server-side. |
| F-09.04 Filtro de palabras | `[NUEVO]` | **AUSENTE, confirmado** | Nada. |
| F-09.05 Anti-necroposting | `[NUEVO]` | **AUSENTE, confirmado** | `reply.php` solo chequea `is_locked`, no la antigüedad del último post. |

---

## Épica F-10 — Navegación y lectura

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-10.01 Índice de categorías | `[YA]` | **PARCIAL — discrepancia con lo declarado** | El índice carga y linkea bien (`foro.js:69-89`), pero **no muestra cantidad de hilos ni último mensaje** como pide la regresión — `categories.php`/`getCategories()` (`ForumRepository.php`) ni siquiera trae esos datos (el `SELECT` no incluye conteo de hilos ni último post). Ver discrepancia abajo. |
| F-10.02 Listado con paginación y orden | `[PARCIAL]` | **PARCIAL, confirmado** | Orden ✔ (`ORDER BY is_pinned DESC, last_post_at DESC`, `ForumRepository.php:72-79`). Paginación real: no — es un `TOP 50` fijo (`getThreadsByCategory($categoryId, $limit = 50)`), sin parámetro de página ni total. Muestra respuestas por hilo (`foro.js:153`) pero no agradecimientos del hilo ni indicador de no leído (depende de F-03.06, ausente). |
| F-10.03 URLs legibles | `[PARCIAL]` | **Más cerca de AUSENTE** | URLs reales: `/foro/hilo/?id=123`, `/foro/categoria/?id=X` — sin slug en ninguna. `forum.categories.slug` existe en el schema pero se genera automático (`slugify($name) . '-' . bin2hex(random_bytes(2))`, `forum_categories.php`) y **nunca se usa en ninguna URL** — es una columna muerta hoy. `forum.threads` no tiene columna slug. Sin meta-tags Open Graph en `foro/hilo/index.php`. |
| F-10.04 Marcar como leído | `[NUEVO]` | **AUSENTE, confirmado** | Depende de F-03.06. |
| F-10.05 Vista móvil | `[PARCIAL]` | **NO VERIFICADO** | El foro hereda el CSS responsive general del sitio (`main.css`, breakpoints de `.card-grid` ya existentes) pero no tiene reglas mobile específicas para `.forum-thread-row`/`.forum-post`/`.forum-category-card`, y no se probó en viewport de 360px en esta sesión. No lo marco PARCIAL ni AUSENTE porque no lo comprobé — hay que testearlo antes de decidir el estado real. |

---

## Épica F-11 — Búsqueda

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-11.01 Buscar en el foro | `[NUEVO]` | **AUSENTE, confirmado** | Sin endpoint de búsqueda, sin UI. La pregunta de si Full-Text Search está instalado en la instancia SQL Server Express del VPS **no se puede responder desde el repo** — hay que verificarlo en vivo (`SELECT * FROM sys.fulltext_catalogs;`), como ya nota el propio backlog. |
| F-11.02 Buscar dentro de un hilo | `[NUEVO]` | **AUSENTE, confirmado** | Depende de F-03.05 (paginación), que tampoco existe. |

---

## Épica F-13 — Administración y operación

> Nota: el backlog pegado salta de F-11 a F-13 — no hay épica F-12 en el documento recibido.
> Mismo caso que el hueco de F-08.01: hueco del documento, no del código.

| Historia | Declarado | Real | Dónde vive / qué falta |
|---|---|---|---|
| F-13.01 CRUD de categorías | `[YA]` | **PARCIAL — discrepancia con lo declarado** | Crear/editar ✔ (`forum_categories.php`, acciones `create`/`update`). Borrar ✔ (con guarda `categoryHasThreads()`). "Reordenar": solo escribiendo un número en `sort_order` a mano, sin drag-and-drop. **"Ocultar categorías": no existe** — no hay columna `is_active`/`is_hidden` en `forum.categories` (`foro_setup.sql:50-61`) ni ningún filtro — toda categoría creada es siempre pública. Ver discrepancia abajo. |
| F-13.02 Permisos por categoría | `[NUEVO]` | **PARCIAL** | Ya existe `admin_only_post` (bloquea creación de hilos a no-admins, `create_thread.php:53-54`) — cubre el caso "solo staff publica". Falta el resto: "solo lectura" no es lo mismo que `admin_only_post` tal como está (hoy solo hay ese único modo), "solo staff" (oculto a comunes) no existe, y el matiz 404-vs-403 para no revelar existencia tampoco. |
| F-13.03 Modo mantenimiento | `[NUEVO]` | **AUSENTE en el foro específicamente** | El sitio ya tiene un lockdown global (`Lockdown.php`, banner/overlay de emergencia) que afecta a toda la API incluido el foro — pero no hay un modo mantenimiento **propio** del foro (solo lectura sin tocar el resto del sitio). |
| F-13.04 Rendimiento del listado | `[NUEVO]` | **AUSENTE — riesgo real** | `foro_setup.sql` no crea **ningún índice** más allá de las primary keys y el unique de `forum.reactions`. En particular `forum.threads.category_id` (filtro de cada listado de categoría) y `forum.posts.thread_id` no tienen índice explícito — el FK no crea uno automático en SQL Server. Con poco volumen no se nota; es la primera cosa a mirar si el foro crece. Los queries del índice de categorías si son de cantidad fija (una query por sección), pero sin conteos de hilos/último post (ver F-10.01) tampoco se puede evaluar ese N+1 todavía porque esa funcionalidad no está. |
| F-13.05 Métricas | `[NUEVO]` | **AUSENTE, confirmado** | Nada. |

---

## Historias marcadas `[YA]` cuyo estado declarado NO coincide con la realidad

Esto es lo que pide el punto 4 de la Etapa 0 explícitamente — encontré **tres**:

1. **F-02.01 (Crear hilo)** — funciona, pero el criterio de regresión "al volver del login conserva lo que había escrito" no está implementado. No rompe el uso normal, pero como regresión textual el criterio falla.
2. **F-10.01 (Índice de categorías)** — el índice funciona pero **no cumple los criterios de regresión tal como están escritos** (no muestra cantidad de hilos ni último mensaje/autor/fecha por categoría). Bajaría de `[YA]` a `[PARCIAL]`.
3. **F-13.01 (CRUD de categorías)** — falta por completo "ocultar categorías" (ni columna ni UI). Bajaría de `[YA]` a `[PARCIAL]`.

---

## Riesgos que encontré y que el backlog no cubre

1. **CSRF, tal como lo pide la Definición de Hecho, no aplica de la forma en que está escrito.** El punto 2 dice "todo endpoint que cambia estado valida CSRF y sesión" — pero este sitio no usa cookies de sesión en ningún lado, el auth es 100% `Authorization: Bearer <JWT>` (`Auth.php`). Un ataque CSRF clásico (que depende de que el browser adjunte credenciales automáticamente) no aplica a este esquema, porque nada se adjunta solo. Si alguien implementa esta épica tomando el punto 2 literal, va a intentar agregar un token CSRF redundante que no resuelve ningún riesgo real acá. Vale la pena que quien planifique la siguiente etapa lo tenga claro para no perder tiempo en eso.

2. **El rate-limit ausente (F-09.01) no es solo sobre "publicar".** `react.php` (reaccionar) y `edit_post.php`/`delete_post.php` tampoco tienen ningún límite — alguien podría scriptear toggle de reacciones a alta frecuencia, o editar/borrar en loop. El backlog solo lo encuadra como problema de flood de posts nuevos.

3. **Locked bloquea también a los admins (ya lo marqué en F-08.04), y es un bug concreto, no una historia nueva** — vale la pena resolverlo en la misma etapa que se toque moderación, no como historia aparte.

4. **`forum.posts` sin paginación (F-03.05) es también un riesgo de rendimiento, no solo de UX.** Un hilo con miles de respuestas devuelve el JSON completo en una sola response y el cliente lo renderiza entero de una — esto cruza directo con F-13.04 (rendimiento) y no está mencionado ahí.

5. **El antispam de registro (Cloudflare Turnstile) ya existe a nivel de sitio** (`register/index.php`, integrado desde 2026-06-02) — el foro hereda esa protección indirectamente porque exige cuenta real del sitio para postear. Vale la pena confirmar en el `.env` de producción que `TURNSTILE_SECRET_KEY` esté cargado — si está vacío, Turnstile queda deshabilitado silenciosamente (es el comportamiento documentado) y el foro pierde esa primera barrera sin que se note.

6. **Falta de transacción en `deletePost()`.** `ForumRepository.php:174-183` hace el `DELETE` y después `touchThreadActivity()` (recalcula `reply_count`) como dos statements separados, no en una transacción. Bajo concurrencia real (poco probable con el volumen actual) hay una ventana chica de inconsistencia. Prioridad baja, lo anoto para no perderlo.

7. **`forum_categories.php` genera un slug (`slugify()+random`) que nunca se usa** (ver F-10.03) — si en algún momento se decide implementar URLs legibles, hay que decidir si se reusa esa columna o se regenera con otro criterio, porque hoy tiene valores tipo `guias-a1b2` pensados para unicidad, no para legibilidad real.

---

## Resumen numérico

53 historias en total en el backlog recibido (F-08.01 y la épica F-12 no vienen en el
documento pegado — ver notas de numeración arriba, son huecos del documento, no del código).

| Estado real | Cantidad | Cuáles |
|---|---|---|
| IMPLEMENTADA (todos los criterios pasan) | 2 | F-03.01, F-05.01 |
| PARCIAL | 14 | F-01.01, F-02.01, F-02.02, F-02.03, F-03.04, F-03.05, F-06.01, F-08.04, F-08.06, F-10.01, F-10.02, F-10.03, F-13.01, F-13.02 |
| AUSENTE | 36 | el resto |
| No verificado (requiere testeo manual, no auditoría de código) | 1 | F-10.05 |

F-02.01 baja de `[YA]` (declarado) a **PARCIAL** en este conteo por el mismo criterio que
F-10.01 y F-13.01: la funcionalidad central anda, pero no todos los criterios de regresión
del backlog se cumplen tal como están escritos (ver "Historias marcadas `[YA]`..." arriba).

**STOP — esperando revisión antes de tocar código**, tal como pide la Etapa 0.
