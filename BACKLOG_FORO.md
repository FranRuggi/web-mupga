# BACKLOG_FORO.md — Historias de usuario del Foro MuPGA

> Documento fuente para el trabajo de mejora del foro (`mupga.com.ar/foro/`).
> Escrito sobre la v1 ya implementada. Cada historia declara su estado presunto;
> **la Etapa 0 obliga a verificarlo contra el repo antes de tocar código.**
>
> Nota de alcance (Franco, 2026-08-11): donde este documento dice "moderador",
> leer "admin" — no hay un rol de moderador separado, es el mismo `dbo.admins`
> que usa el resto del ControlPanel. Ver `GAP_FORO.md` para el detalle real
> contra el repo.

---

## 1. Contexto técnico confirmado (v1)

| Aspecto | Estado actual |
|---|---|
| Formato de mensaje | "markdown-lite" propio (`renderRichText()` en `app.js`), mismo que Noticias. Soporta `**negrita**`, `*cursiva*`, `__subrayado__`, `~~tachado~~`, `## subtítulo`, `- ` listas, `[texto](url)` solo http/https |
| Sanitización | Whitelist regex propia. `esc()` escapa todo primero, después se aplican los reemplazos. Sin HTMLPurifier |
| Dónde renderiza | **Client-side**. La base guarda el markdown-lite crudo, no HTML |
| Auth | JWT del sitio (`TokenService` / `requireAuth()`). `author_account` = `usr` del token |
| Personaje | `CharacterRepository::getMainCharacterName()` una sola vez, al crear el post. Guardado denormalizado |
| Schema | `forum` dentro de `mupga_admin`. Login `mupga_forum_svc`, dueño solo de ese schema |
| Imágenes | No implementado. v1 es texto puro |
| Moderación | Todo o nada: cualquier cuenta en `mupga_admin.dbo.admins` con `active=1` puede todo |
| Reacciones | Una sola ("🙏 Agradecer"), toggle por cuenta |
| Alcance construido | Hilos, respuestas, reacciones, categorías, bans, moderación |

**Restricciones del proyecto que aplican a todo este backlog:**

- Nunca escribir en tablas del juego. Lectura sí, escritura jamás.
- SQL Server Express: sin `STRING_AGG` → usar `STUFF + FOR XML PATH`.
- `GETDATE()` devuelve hora Argentina. UTC = `DATEADD(HOUR, 3, GETDATE())`.
- Nada de triggers sobre `MEMB_INFO`.
- `WEB_ENGINE_*` está fuera de límites.
- Credenciales solo por `.env` / `getenv()`. PDO preparado siempre.
- Notificaciones (Discord/email) en try/catch independiente: si fallan, el INSERT no se revierte.

---

## 2. Roles

| Rol | Definición |
|---|---|
| **Visitante** | Sin sesión. Solo lectura de categorías públicas |
| **Usuario** | Cuenta del sitio con JWT válido |
| **Usuario verificado** | Usuario con al menos un personaje creado en la cuenta |
| **Autor** | Usuario dueño del hilo o post en cuestión |
| **Admin** | Cuenta en `mupga_admin.dbo.admins` con `active=1` |

---

## 3. Convenciones

- **ID**: `F-<épica>.<historia>`.
- **Estado**: `[YA]` implementado · `[PARCIAL]` existe incompleto · `[NUEVO]` no existe.
- **Prioridad**: `P0` bloqueante/riesgo · `P1` alto valor · `P2` deseable.
- Criterios en Gherkin. Si un criterio no se puede verificar manualmente, está mal escrito.

**Definición de Hecho (aplica a toda historia):**
1. Todos los criterios de aceptación pasan manualmente.
2. Todo endpoint que cambia estado valida CSRF y sesión.
3. Toda query usa PDO preparado.
4. Ningún permiso nuevo se otorga a `mupga_forum_svc` fuera del schema `forum`.
5. Migración SQL idempotente y aditiva (nada de `DROP` sobre datos existentes).
6. No se rompe ninguna historia marcada `[YA]`.

---

# ÉPICA F-01 — Integridad del contenido y renderizado

*El punto más frágil de la v1: el escape vive en el cliente, la base guarda crudo.*

### F-01.01 — Validación server-side del cuerpo `[NUEVO]` `P0`
**Como** dueño del sistema
**quiero** que el servidor valide el contenido antes de guardarlo
**para** que la integridad no dependa de que el cliente se porte bien.

**Criterios**
- Dado un POST directo a la API con cuerpo vacío o solo espacios, cuando se procesa, entonces responde 422 y no inserta nada.
- Dado un cuerpo que excede el máximo (título 120 chars, hilo 10.000, respuesta 5.000), entonces responde 422 con el límite en el mensaje.
- Dado un cuerpo con más de 25 saltos de línea consecutivos, entonces se normaliza a un máximo de 2 antes de guardar.
- Dado un cuerpo con caracteres de control o `​` repetidos (padding invisible), entonces se limpian antes de guardar.

**Notas técnicas**
- Validador único compartido por crear-hilo y crear-respuesta. Un solo lugar, no dos copias.
- Los límites viven en constantes, no hardcodeados en cada endpoint.

---

### F-01.02 — Escape defensivo en la API `[NUEVO]` `P0`
**Como** dueño del sistema
**quiero** que la API nunca devuelva contenido que un cliente descuidado pueda inyectar
**para** cerrar el riesgo de XSS si mañana aparece otro consumidor (app, bot de Discord, mail).

**Criterios**
- Dado un post cuyo cuerpo contiene `<script>`, cuando lo pido por API, entonces el JSON lo devuelve escapado o con una bandera explícita de que viene crudo y debe escaparse.
- Dado el mismo post, cuando lo veo en el foro, entonces se ve el texto literal `<script>` y no se ejecuta nada.
- Dado que se agrega el escape, entonces el markdown-lite sigue renderizando idéntico a hoy (negrita, links, listas).

**Notas técnicas**
- Decidir y documentar el contrato: la API devuelve crudo y el cliente escapa (hoy), o la API devuelve escapado. Mixto es el peor caso.
- Testear específicamente `[click](javascript:alert(1))` y `[click](data:text/html,...)`: el whitelist de esquema debe estar también server-side, no solo en la regex del cliente.

---

### F-01.03 — Renderizador compartido server-side `[NUEVO]` `P2`
**Como** dueño del sistema
**quiero** una versión PHP del `renderRichText()`
**para** poder mostrar posts formateados en mails, embeds de Discord y meta-tags.

**Criterios**
- Dado un texto markdown-lite, cuando lo renderiza PHP y lo renderiza JS, entonces el HTML resultante es idéntico.
- Dado un post, cuando se genera su meta-description para compartir, entonces se ve texto plano sin asteriscos ni corchetes.

**Fuera de alcance:** reemplazar el render del cliente. Conviven.

---

### F-01.04 — Vista previa antes de publicar `[NUEVO]` `P1`
**Como** usuario
**quiero** ver cómo va a quedar mi mensaje antes de mandarlo
**para** no publicar con el formato roto.

**Criterios**
- Dado que escribo en el editor, cuando toco "Vista previa", entonces veo el mensaje renderizado con el mismo CSS que tendrá publicado.
- Dado que estoy en vista previa, cuando toco "Editar", entonces vuelvo al textarea con el texto y la posición del cursor intactos.
- Dado que escribo un link mal formado, entonces la vista previa lo muestra como texto plano (comportamiento real), no lo corrige.

---

# ÉPICA F-02 — Autoría de hilos

### F-02.01 — Crear hilo `[YA]` `P0`
Cubierta por v1. Se documenta como base de regresión.

**Criterios de regresión**
- Dado un usuario logueado en una categoría abierta, cuando publica título + cuerpo, entonces el hilo aparece primero en el listado y redirige a su permalink.
- Dado un visitante, cuando intenta abrir el formulario, entonces se le pide login y al volver conserva lo que había escrito.

---

### F-02.02 — Editar hilo propio con ventana de tiempo `[PARCIAL]` `P1`
**Como** autor
**quiero** poder corregir mi hilo dentro de un plazo
**para** arreglar errores sin poder reescribir la historia de una discusión vieja.

**Criterios**
- Dado que soy el autor y pasaron menos de 30 minutos, cuando edito, entonces se guarda y aparece "editado hace X" bajo el mensaje.
- Dado que pasaron más de 30 minutos, entonces el botón "Editar" no aparece para mí (sí para admin).
- Dado que un admin edita un post ajeno, entonces figura "editado por un moderador" sin exponer qué admin fue al público.
- Dado cualquier edición, entonces queda registro en la tabla de auditoría (F-08.05) con el cuerpo anterior.

**Notas técnicas**
- La ventana en constante configurable.
- No sobreescribir el cuerpo original en la misma fila: guardar la versión previa en la tabla de auditoría.

---

### F-02.03 — Borrar hilo propio (soft delete) `[PARCIAL]` `P1`
**Como** autor
**quiero** borrar mi hilo
**para** retirar algo que publiqué por error.

**Criterios**
- Dado que soy autor y mi hilo no tiene respuestas de terceros, cuando borro, entonces desaparece del listado.
- Dado que mi hilo ya tiene respuestas, entonces no puedo borrarlo yo: solo puedo pedirlo a un moderador.
- Dado un hilo borrado, cuando un admin entra a la papelera, entonces lo ve y puede restaurarlo.
- Dado un hilo borrado, cuando alguien entra por el permalink viejo, entonces recibe 404, no un error 500.

**Notas técnicas**
- `deleted_at` + `deleted_by`. Nada de `DELETE` físico.

---

### F-02.04 — Borrador autoguardado `[NUEVO]` `P2`
**Como** usuario
**quiero** no perder un texto largo si se me cierra la pestaña
**para** no tener que reescribir una guía entera.

**Criterios**
- Dado que escribí más de 200 caracteres, cuando cierro y vuelvo a abrir el editor de esa categoría, entonces me ofrece recuperar el borrador.
- Dado que publico el hilo, entonces el borrador se descarta.
- Dado que hay un borrador de más de 7 días, entonces se descarta solo.

**Notas técnicas**
- `localStorage` con clave por categoría + cuenta. No requiere backend.

---

### F-02.05 — Prefijos por categoría `[NUEVO]` `P2`
**Como** usuario
**quiero** etiquetar mi hilo (`[Guía]`, `[Duda]`, `[Vendo]`, `[Bug]`)
**para** que se entienda de qué va sin abrirlo.

**Criterios**
- Dada una categoría con prefijos configurados, cuando creo un hilo, entonces debo elegir uno de la lista (no texto libre).
- Dado el listado, cuando hay prefijos, entonces puedo filtrar por prefijo sin recargar la página.
- Dada una categoría sin prefijos configurados, entonces el selector no aparece.

---

# ÉPICA F-03 — Respuestas

### F-03.01 — Responder en un hilo `[YA]` `P0`
Base de regresión: el usuario logueado responde, la respuesta aparece al final, el contador del hilo y la fecha de última actividad se actualizan.

---

### F-03.02 — Citar un mensaje `[NUEVO]` `P1`
**Como** usuario
**quiero** citar el mensaje de otro
**para** que se entienda a qué respondo en un hilo largo.

**Criterios**
- Dado un post, cuando toco "Citar", entonces el editor se abre con el bloque de cita precargado (autor + permalink) y el cursor debajo.
- Dado que el post citado fue borrado, cuando cargo el hilo, entonces la cita muestra "[mensaje eliminado]" sin romper el layout.
- Dado que cito un post que ya contiene una cita, entonces se conserva un solo nivel de anidamiento.
- Dado un bloque de cita, cuando toco el link del autor, entonces salto al post original resaltado.

**Notas técnicas**
- Sintaxis nueva del markdown-lite: `> ` para cita, con una primera línea de metadata. Agregar al render de cliente **y** documentar en F-01.03.
- Un solo nivel se garantiza al generar, no al renderizar.

---

### F-03.03 — Mencionar a otro usuario `[NUEVO]` `P1`
**Como** usuario
**quiero** escribir `@nombre` y que le llegue aviso
**para** traer a alguien a la conversación.

**Criterios**
- Dado que escribo `@` seguido de 2+ caracteres, entonces aparece un autocompletado con cuentas que ya participaron en ese hilo, primero.
- Dado que publico con una mención válida, entonces el nombre queda como link al perfil y se genera la notificación (F-07.02).
- Dado que menciono a alguien inexistente, entonces queda como texto plano, sin link ni error.
- Dado que menciono a la misma persona 5 veces en un post, entonces recibe una sola notificación.

**Notas técnicas**
- El autocompletado nunca debe filtrar la lista completa de cuentas del servidor: solo participantes del hilo + coincidencia exacta.

---

### F-03.04 — Editar y borrar respuesta propia `[PARCIAL]` `P1`
Mismas reglas que F-02.02 y F-02.03 aplicadas a respuestas.

**Criterios**
- Dado que borro mi respuesta y hay respuestas posteriores, entonces queda el placeholder "[mensaje eliminado por el autor]" para no romper la lectura.
- Dado que borro mi respuesta y es la última del hilo, entonces desaparece y el contador se ajusta.

---

### F-03.05 — Permalink y paginación de respuestas `[PARCIAL]` `P1`
**Como** usuario
**quiero** linkear una respuesta puntual
**para** compartirla en Discord.

**Criterios**
- Dado un hilo de 80 respuestas, entonces se pagina de a 20 y la URL refleja la página.
- Dado el link `#post-{id}` de una respuesta de la página 3, cuando lo abro, entonces caigo en la página 3 con ese post resaltado.
- Dado que estoy en la última página, entonces el editor de respuesta está visible sin tener que navegar.

---

### F-03.06 — Ir al primer mensaje no leído `[NUEVO]` `P2`
**Como** usuario que sigue un hilo largo
**quiero** retomar donde dejé
**para** no scrollear buscando dónde iba.

**Criterios**
- Dado que ya leí hasta la respuesta 40 y hay 55, cuando entro desde el listado, entonces caigo en la 41 con un separador "Nuevos mensajes".
- Dado que nunca abrí el hilo, entonces caigo en el primer mensaje.
- Dado un visitante sin sesión, entonces el indicador de no leídos no aparece.

**Notas técnicas**
- Tabla `forum.thread_reads (account, thread_id, last_post_id, read_at)`. Escritura al abrir el hilo, no en cada scroll.

---

# ÉPICA F-04 — Formato del mensaje

### F-04.01 — Barra de formato en el editor `[NUEVO]` `P1`
**Como** usuario
**quiero** botones para dar formato
**para** no tener que acordarme de la sintaxis.

**Criterios**
- Dado texto seleccionado, cuando toco "Negrita", entonces se envuelve en `**` y la selección se mantiene.
- Dado que no hay selección, cuando toco "Negrita", entonces se insertan `****` con el cursor en el medio.
- Dado que toco "Negrita" sobre texto que ya está en negrita, entonces se quita el formato.
- Dado el móvil, entonces la barra queda visible sobre el teclado, no tapada por él.

**Notas técnicas**
- Atajos `Ctrl/Cmd+B`, `+I`, `+K` (link).

---

### F-04.02 — Bloque de código `[NUEVO]` `P2`
**Como** usuario que comparte configuraciones
**quiero** un bloque monoespaciado
**para** pegar rutas, comandos o líneas de `.ini` sin que se rompan.

**Criterios**
- Dado texto entre triple backtick, entonces se renderiza monoespaciado con fondo diferenciado y scroll horizontal.
- Dado un bloque de código, entonces el markdown-lite **no** se aplica adentro (los `**` se ven literales).
- Dado un bloque de código en móvil, entonces no desborda el ancho de la pantalla.

---

### F-04.03 — Spoiler / contenido plegado `[NUEVO]` `P2`
**Como** usuario que escribe una guía larga
**quiero** ocultar bloques
**para** que el post se pueda escanear.

**Criterios**
- Dada la sintaxis de spoiler con título, cuando cargo el post, entonces veo solo el título y un chevron.
- Dado que toco el título, entonces se despliega el contenido con el markdown-lite aplicado adentro.
- Dado un spoiler dentro de otro spoiler, entonces solo se procesa el externo.

---

### F-04.04 — Embed de YouTube `[NUEVO]` `P2`
**Como** usuario
**quiero** que un link de YouTube se vea como video
**para** compartir clips del server.

**Criterios**
- Dado un link de YouTube solo en su línea, entonces se renderiza como iframe responsive 16:9.
- Dado un link de YouTube dentro de un párrafo, entonces queda como link normal.
- Dado más de 3 embeds en un mismo post, entonces del cuarto en adelante quedan como links.
- Dado el iframe, entonces usa `youtube-nocookie.com` y `loading="lazy"`.

**Notas técnicas**
- La extracción del ID va server-side también, para no confiar en la regex del cliente.

---

### F-04.05 — Imágenes en posts `[NUEVO]` `P1`
**Como** usuario
**quiero** subir capturas
**para** mostrar un bug, un item o un evento.

**Criterios**
- Dado que soy usuario verificado, cuando adjunto una imagen, entonces se sube por presigned URL a R2 y se inserta la referencia en el cuerpo.
- Dado un archivo que no es jpg/png/webp o supera 5 MB, entonces se rechaza antes de subir con mensaje claro.
- Dado que subo la imagen pero abandono sin publicar, entonces el objeto queda huérfano y se limpia por job (o se sube solo al confirmar).
- Dada una imagen en el post, cuando la toco, entonces se abre en visor a tamaño completo.
- Dado un post con imagen, cuando un moderador lo borra, entonces la imagen deja de ser accesible públicamente.

**Notas técnicas**
- Portar el patrón de Reclamos: `upload_url.php` / `finalize.php`. **Bucket separado** (`mupga-foro`), no reusar `mupga-reclamos`.
- Cuota por cuenta y por día para evitar que R2 se convierta en hosting gratis.

---

# ÉPICA F-05 — Reacciones

### F-05.01 — Agradecer un mensaje `[YA]` `P0`
Base de regresión: toggle on/off, un registro por cuenta y post.

**Criterios de regresión**
- Dado que ya agradecí, cuando vuelvo a tocar, entonces se quita y el contador baja.
- Dado que no tengo sesión, entonces el botón me lleva al login.

---

### F-05.02 — No agradecerse a uno mismo `[NUEVO]` `P1`
**Como** dueño del sistema
**quiero** impedir el autoagradecimiento
**para** que el contador signifique algo.

**Criterios**
- Dado mi propio post, entonces el botón aparece deshabilitado con tooltip.
- Dado un POST directo a la API sobre mi propio post, entonces responde 403.

---

### F-05.03 — Ver quiénes agradecieron `[NUEVO]` `P2`
**Como** usuario
**quiero** ver quién agradeció
**para** saber a quién le sirvió mi guía.

**Criterios**
- Dado un post con agradecimientos, cuando toco el contador, entonces veo la lista con nombre de personaje y fecha.
- Dado un post con más de 20, entonces se muestran los primeros 20 y "y N más".
- Dado un post sin agradecimientos, entonces el contador no es clickeable.

---

### F-05.04 — Set ampliado de reacciones `[NUEVO]` `P2`
**Como** usuario
**quiero** más de una reacción disponible
**para** expresar algo distinto a "gracias".

**Criterios**
- Dado un post, cuando mantengo presionado el botón, entonces aparece el selector con el set definido (ej: 🙏 Gracias, 👍 De acuerdo, 😂, 🔥).
- Dado que ya reaccioné con una, cuando elijo otra, entonces se reemplaza (una por cuenta y post).
- Dado el listado agrupado, entonces se muestran solo los tipos con al menos una reacción.

**Notas técnicas**
- Requiere migrar la tabla actual agregando `reaction_type` con default = el tipo existente. Migración aditiva, sin perder datos.
- **Decisión pendiente de Franco:** puede que convenga quedarse con una sola reacción. Esta historia es opcional.

---

# ÉPICA F-06 — Identidad del autor

### F-06.01 — Personaje desactualizado `[PARCIAL]` `P1`
**Como** usuario que borró o cambió su personaje principal
**quiero** que mis posts no muestren un nombre muerto
**para** que se me siga reconociendo.

**Criterios**
- Dado un post cuyo `character_name` denormalizado ya no existe en la base de juego, cuando se muestra, entonces se ve el nombre guardado sin romper nada.
- Dado que publico un post nuevo, entonces se resuelve el personaje principal actual.
- Dado un job de refresco (o acción manual del usuario "actualizar mi identidad"), entonces se actualiza el nombre en mis posts.

**Notas técnicas**
- La resolución sigue siendo lectura pura contra la base de juego. Nunca escritura.
- No re-consultar en cada render: eso multiplica carga sobre la base del juego.

---

### F-06.02 — Perfil público del usuario `[NUEVO]` `P2`
**Como** usuario
**quiero** ver el perfil de otro participante
**para** saber quién es y qué escribió.

**Criterios**
- Dado un nombre de autor, cuando lo toco, entonces veo su perfil: personaje principal, fecha de registro, cantidad de posts, agradecimientos recibidos, últimos 10 hilos.
- Dado un perfil, entonces **no** se muestra el nombre de cuenta ni el mail, solo el personaje.
- Dada una cuenta baneada del foro, entonces el perfil lo indica.

**Notas técnicas**
- Cuidado con exponer `author_account`: la cuenta de login no debe ser pública.

---

### F-06.03 — Distintivos del autor `[NUEVO]` `P2`
**Como** usuario
**quiero** distinguir de un vistazo al staff, a los VIP y al guild
**para** dar más peso a lo que dice cada uno.

**Criterios**
- Dado un post de una cuenta en `admins` activa, entonces muestra el distintivo de staff.
- Dado un post de una cuenta con VIP vigente, entonces muestra el distintivo VIP con su nivel.
- Dado un autor con guild, entonces se muestra el tag junto al nombre.
- Dado que la base de juego no responde, entonces los distintivos se omiten y el post se muestra igual.

**Notas técnicas**
- Denormalizar igual que el personaje, no consultar en cada render.

---

### F-06.04 — Firma `[NUEVO]` `P2`
**Como** usuario
**quiero** una firma
**para** poner mi guild o mi canal.

**Criterios**
- Dado que configuro una firma de hasta 200 caracteres, entonces aparece bajo cada post mío, separada visualmente.
- Dada la firma, entonces solo se permite markdown-lite básico y máximo 1 link.
- Dado que un usuario desactiva "ver firmas" en sus preferencias, entonces no ve ninguna.
- Dado un hilo, entonces la firma se muestra una sola vez por autor por página.

---

# ÉPICA F-07 — Notificaciones

### F-07.01 — Seguir un hilo `[NUEVO]` `P1`
**Como** usuario
**quiero** enterarme cuando responden en un hilo que me interesa
**para** no tener que volver a revisar.

**Criterios**
- Dado que respondo en un hilo, entonces paso a seguirlo automáticamente.
- Dado un hilo que no respondí, cuando toco "Seguir", entonces empiezo a recibir avisos.
- Dado que dejo de seguir, entonces no recibo más avisos aunque siga respondiendo gente.
- Dado que hay 5 respuestas nuevas desde mi última visita, entonces recibo **una** notificación agrupada, no 5.

---

### F-07.02 — Centro de notificaciones `[NUEVO]` `P1`
**Como** usuario
**quiero** ver mis avisos en el sitio
**para** no depender del mail.

**Criterios**
- Dado que tengo avisos sin leer, entonces la campanita muestra el contador en el header del foro.
- Dado que abro el panel, entonces veo los últimos 20 con link directo al post que los originó.
- Dado que toco un aviso, entonces queda leído y el contador baja.
- Dado que toco "Marcar todo como leído", entonces el contador queda en 0.
- Dados avisos de más de 60 días, entonces se purgan.

**Tipos de aviso:** respuesta en hilo seguido · mención · agradecimiento recibido · acción de moderación sobre mi contenido.

---

### F-07.03 — Aviso a Discord de hilos nuevos `[NUEVO]` `P2`
**Como** admin
**quiero** que los hilos nuevos aparezcan en un canal de Discord
**para** que la comunidad vea el movimiento del foro.

**Criterios**
- Dado un hilo nuevo en una categoría marcada como "difundir", entonces se manda un embed con título, autor, categoría y link.
- Dado que el webhook falla, entonces el hilo se publica igual y el error queda logueado.
- Dado un hilo en una categoría no marcada, entonces no se difunde nada.

**Notas técnicas**
- Reusar el patrón del módulo de Reclamos. Try/catch independiente, siempre.

---

# ÉPICA F-08 — Moderación

*Hoy es todo-o-nada. Esta épica introduce granularidad y trazabilidad.*

### F-08.02 — Reportar contenido `[NUEVO]` `P0`
**Como** usuario
**quiero** avisar de un post que rompe las reglas
**para** que el staff lo revise sin tener que escribir por Discord.

**Criterios**
- Dado un post, cuando toco "Reportar", entonces elijo un motivo de una lista y agrego un comentario opcional.
- Dado que ya reporté ese post, entonces no puedo volver a reportarlo.
- Dado un reporte enviado, entonces el autor del post **no** se entera.
- Dado un reporte, entonces se manda aviso a Discord del staff.
- Dado que reporto 10 posts en una hora, entonces se me bloquea la función por 24 h.

---

### F-08.03 — Cola de reportes `[NUEVO]` `P0`
**Como** moderador
**quiero** una bandeja de reportes
**para** no depender de que alguien me lo pase por Discord.

**Criterios**
- Dada la cola, entonces veo los reportes pendientes ordenados por antigüedad, con contexto del post.
- Dado un reporte, entonces puedo resolverlo como "acción tomada" o "sin mérito", con nota interna.
- Dado que resuelvo un reporte, entonces los demás reportes sobre ese mismo post se cierran juntos.
- Dados varios moderadores, entonces veo si otro ya lo está mirando.

---

### F-08.04 — Acciones sobre hilos `[PARCIAL]` `P1`
**Como** admin
**quiero** fijar, cerrar y mover hilos
**para** ordenar el foro.

**Criterios**
- Dado un hilo fijado, entonces aparece arriba del listado de su categoría con indicador visual.
- Dado un hilo cerrado, entonces se lee pero nadie puede responder (incluido el autor); moderadores sí.
- Dado que muevo un hilo, entonces el permalink viejo redirige al nuevo, no da 404.
- Dada cualquiera de estas acciones, entonces queda un aviso visible en el hilo ("Cerrado por el staff — motivo").

---

### F-08.05 — Log de auditoría `[NUEVO]` `P0`
**Como** admin
**quiero** registro de toda acción de moderación
**para** poder revisar qué pasó y quién lo hizo.

**Criterios**
- Dada cualquier acción de moderación (editar, borrar, cerrar, fijar, mover, banear), entonces se registra actor, acción, objetivo, motivo y timestamp UTC.
- Dado un contenido editado o borrado por moderación, entonces el cuerpo original queda guardado.
- Dado el log, entonces se puede filtrar por moderador y por rango de fechas.
- Dado el log, entonces nadie puede borrar entradas desde la interfaz.

**Notas técnicas**
- Timestamps con `DATEADD(HOUR, 3, GETDATE())`. Nada de `GETUTCDATE()`.

---

### F-08.06 — Ban del foro con motivo y vencimiento `[PARCIAL]` `P1`
**Como** admin
**quiero** banear del foro con plazo y motivo
**para** sancionar sin tocar el acceso al juego.

**Criterios**
- Dado un ban con vencimiento, cuando el usuario intenta postear, entonces ve el motivo y hasta cuándo dura.
- Dado un usuario baneado del foro, entonces puede seguir jugando y usando el resto del sitio normalmente.
- Dado un ban vencido, entonces el usuario vuelve a poder postear sin intervención manual.
- Dado un ban, entonces sus posts anteriores siguen visibles salvo que se borren aparte.

---

# ÉPICA F-09 — Antiabuso

### F-09.01 — Límite de publicación `[NUEVO]` `P0`
**Como** dueño del sistema
**quiero** frenar el flood
**para** que un script no llene el foro en dos minutos.

**Criterios**
- Dado que publiqué hace menos de 30 segundos, entonces el siguiente post se rechaza con el tiempo restante.
- Dado que publiqué 10 posts en la última hora, entonces se me bloquea hasta que baje del umbral.
- Dado un moderador, entonces los límites no le aplican.
- Dado el rechazo, entonces el texto escrito no se pierde.

**Notas técnicas**
- El límite se evalúa **server-side** contra la base, no con un contador de sesión.

---

### F-09.02 — Requisito de personaje para postear `[NUEVO]` `P1`
**Como** dueño del sistema
**quiero** que solo pueda postear quien tiene un personaje
**para** cortar cuentas creadas solo para spamear.

**Criterios**
- Dada una cuenta sin personajes, cuando intenta crear un hilo, entonces se le pide crear un personaje primero.
- Dada la misma cuenta, entonces puede leer todo el foro sin restricción.
- Dado que crea un personaje, entonces puede postear sin esperar nada.

---

### F-09.03 — Links restringidos para cuentas nuevas `[NUEVO]` `P1`
**Como** dueño del sistema
**quiero** que las cuentas nuevas no puedan pegar links externos
**para** cortar el spam de servidores rivales.

**Criterios**
- Dada una cuenta con menos de 5 posts, cuando publica un link externo, entonces el link se guarda como texto plano.
- Dado un link a un dominio de la whitelist (mupga.com.ar, youtube, imgur, discord), entonces se permite siempre.
- Dado que la cuenta supera el umbral, entonces sus links nuevos funcionan normalmente (los viejos no se reprocesan).

---

### F-09.04 — Filtro de palabras `[NUEVO]` `P2`
**Como** moderador
**quiero** una lista de términos vetados
**para** frenar automáticamente lo evidente.

**Criterios**
- Dado un post con un término de la lista "bloquear", entonces se rechaza con mensaje genérico.
- Dado un post con un término de la lista "revisar", entonces se publica pero entra a la cola de reportes marcado.
- Dada la lista, entonces se administra desde el panel sin tocar código.
- Dado el filtro, entonces no matchea dentro de palabras más largas.

---

### F-09.05 — Anti-necroposting `[NUEVO]` `P2`
**Como** dueño del sistema
**quiero** avisar antes de revivir un hilo viejo
**para** que el listado no se llene de hilos de hace un año.

**Criterios**
- Dado un hilo sin actividad hace más de 90 días, cuando voy a responder, entonces recibo una advertencia que debo confirmar.
- Dado que confirmo, entonces se publica normal.
- Dado el subforo de compra/venta, entonces la advertencia aparece a los 30 días.

---

# ÉPICA F-10 — Navegación y lectura

### F-10.01 — Índice de categorías `[YA]` `P0`
Regresión: cada categoría muestra descripción, cantidad de hilos y último mensaje con autor y fecha.

---

### F-10.02 — Listado con paginación y orden `[PARCIAL]` `P1`
**Criterios**
- Dada una categoría con más de 25 hilos, entonces se pagina y la URL refleja la página.
- Dado el listado, entonces el orden por defecto es última actividad descendente, con los fijados arriba.
- Dado el listado, entonces cada fila muestra prefijo, título, autor, respuestas, agradecimientos y última actividad.
- Dado un hilo con mensajes que no leí, entonces se distingue visualmente.

---

### F-10.03 — URLs legibles `[PARCIAL]` `P2`
**Criterios**
- Dado un hilo, entonces su URL incluye slug del título (`/foro/hilo/123-guia-de-reset`).
- Dado un slug incorrecto con ID correcto, entonces redirige 301 al slug bueno.
- Dado un título con tildes o emojis, entonces el slug se normaliza sin romperse.
- Dado un hilo, entonces tiene meta-tags Open Graph con título y extracto para que se vea bien al compartirlo en Discord.

---

### F-10.04 — Marcar como leído `[NUEVO]` `P2`
**Criterios**
- Dado el índice, cuando toco "Marcar todo como leído", entonces desaparecen todos los indicadores de no leído.
- Dada una categoría, entonces puedo marcarla leída sin afectar las otras.

---

### F-10.05 — Vista móvil `[PARCIAL]` `P1`
**Criterios**
- Dado un teléfono de 360 px, entonces ninguna vista requiere scroll horizontal.
- Dado el listado en móvil, entonces cada fila es tocable entera, no solo el título.
- Dado el editor en móvil, entonces el botón de publicar queda accesible sin cerrar el teclado.

---

# ÉPICA F-11 — Búsqueda

### F-11.01 — Buscar en el foro `[NUEVO]` `P1`
**Como** usuario
**quiero** buscar antes de preguntar
**para** encontrar si ya lo respondieron.

**Criterios**
- Dado un término de 3+ caracteres, entonces obtengo resultados de títulos y cuerpos con el término resaltado.
- Dado un término de menos de 3 caracteres, entonces se me pide ampliar la búsqueda.
- Dados los resultados, entonces puedo filtrar por categoría y por autor.
- Dada una búsqueda sin resultados, entonces se me ofrece crear un hilo con ese título.
- Dado el foro con 10.000 posts, entonces la búsqueda responde en menos de 2 segundos.

**Notas técnicas**
- Verificar si Full-Text Search está instalado en la instancia Express. Si no está, índice sobre título + `LIKE` acotado por categoría, y documentar el límite.
- Nada de `LIKE '%termino%'` sobre la tabla entera sin filtro previo.

---

### F-11.02 — Buscar dentro de un hilo `[NUEVO]` `P2`
**Criterios**
- Dado un hilo de más de 3 páginas, entonces tengo un buscador acotado a ese hilo.
- Dado un resultado, cuando lo toco, entonces salto a la página y post correspondiente.

---

# ÉPICA F-13 — Administración y operación

### F-13.01 — CRUD de categorías `[YA]` `P0`
Regresión: crear, editar, reordenar y ocultar categorías desde el panel.

---

### F-13.02 — Permisos por categoría `[NUEVO]` `P2`
**Criterios**
- Dada una categoría marcada "solo lectura", entonces solo el staff puede crear hilos, todos pueden leer.
- Dada una categoría marcada "solo staff", entonces no aparece para usuarios comunes ni en búsqueda.
- Dada una categoría oculta, entonces sus hilos devuelven 404 a quien no tiene permiso, no 403 (no revelar existencia).

---

### F-13.03 — Modo mantenimiento `[NUEVO]` `P2`
**Criterios**
- Dado el sitio en mantenimiento, entonces el foro queda en solo lectura con banner, o cerrado, según configuración.
- Dado el staff, entonces puede seguir operando durante el mantenimiento.

---

### F-13.04 — Rendimiento del listado `[NUEVO]` `P1`
**Como** dueño del sistema
**quiero** que el foro no castigue la base
**para** que no compita con el GameServer.

**Criterios**
- Dado el índice de categorías, entonces se resuelve en una cantidad fija de queries, sin N+1 por categoría.
- Dado un hilo de 100 respuestas, entonces la página se resuelve en una cantidad fija de queries.
- Dadas las vistas principales, entonces existen índices sobre las columnas de filtro y orden (categoría, última actividad, hilo+fecha).
- Dado el foro bajo carga, entonces ninguna query del foro toca la base de juego en el render.

---

### F-13.05 — Métricas del foro `[NUEVO]` `P2`
**Criterios**
- Dado el panel, entonces veo hilos y posts por día de los últimos 30 días.
- Dado el panel, entonces veo las 10 cuentas más activas y las categorías más movidas.

---

# 4. Cómo ejecutar esto con Claude Code

## Etapa 0 — Auditoría (obligatoria, sin escribir código)

> Vas a trabajar sobre el foro del repo `web-mupga`. **No implementes nada en esta etapa.**
>
> 1. Explorá el código real del foro: endpoints PHP, schema `forum` en `mupga_admin`, JS del cliente, y el gate de admin.
> 2. Leé `BACKLOG_FORO.md`.
> 3. Generá `GAP_FORO.md`: una fila por historia con estado real (IMPLEMENTADA / PARCIAL / AUSENTE), el archivo y la línea donde vive lo que ya existe, y qué falta exactamente en las parciales.
> 4. Marcá toda historia cuyo estado declarado en el backlog no coincida con la realidad del repo.
> 5. Listá los riesgos que encuentres y que el backlog no cubre.
>
> **STOP.** No sigas hasta que revise el gap.

## Orden sugerido de implementación

| Etapa | Contenido | Por qué primero |
|---|---|---|
| 1 | F-01.01, F-01.02, F-09.01 | Integridad y flood. Es lo que rompe si alguien se pone creativo |
| 2 | F-08.01, F-08.02, F-08.03, F-08.05 | Sin cola de reportes ni auditoría, moderar no escala |
| 3 | F-02.02, F-02.03, F-03.04, F-08.04, F-08.06 | Cerrar el ciclo de vida del contenido |
| 4 | F-03.02, F-03.03, F-07.01, F-07.02 | Lo que hace que la gente vuelva |
| 5 | F-04.01, F-01.04, F-04.05 | Calidad de escritura |
| 6 | F-10.x, F-11.01, F-13.04 | Navegación, búsqueda, performance |
| 7 | El resto (P2) | Según lo que pida la comunidad |

**STOP entre cada etapa.** Cada una se prueba en producción antes de arrancar la siguiente.
