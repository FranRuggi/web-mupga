-- ============================================================
-- MuPGA Foro — Categorías iniciales + avisos fijados
-- Ejecutar como sa en mupga_admin, DESPUÉS de foro_setup.sql y
-- foro_migracion_v2.sql (usa las columnas is_hidden y locked_reason
-- que agrega la v2). No depende de la v3.
--
-- Es re-ejecutable: cada INSERT está guardado por IF NOT EXISTS, así
-- que si agregás una categoría al final y volvés a correrlo, solo
-- inserta lo que falta. Editar una categoría ya creada se hace desde
-- el ControlPanel, no tocando este script.
--
-- ⚠️ ANTES DE EJECUTAR:
--   1. Poné tu cuenta del sitio en @autor (abajo).
--   2. El archivo está en UTF-8 (con BOM) por los emojis de los
--      nombres. Si al abrirlo en SSMS ves caracteres raros, guardalo
--      como UTF-8 antes de ejecutar o los emojis entran rotos a la
--      base. Si preferís no arriesgar, sacá los emojis de los N'...'
--      de la sección A: no afectan a nada más.
--
-- Los textos de los avisos son un punto de partida — están pensados
-- para editarse desde el foro mismo (quedan a nombre de @autor, así
-- que te aparece el botón Editar sin límite de tiempo por ser admin).
-- ============================================================

USE mupga_admin;
GO

-- Cuenta del sitio (memb___id) que queda como dueña de los avisos: la
-- misma con la que entrás al ControlPanel. Sirve para poder editarlos
-- después desde la web. El nombre que se MUESTRA es @firma, no la cuenta.
DECLARE @autor  VARCHAR(10)   = 'CAMBIAME';
DECLARE @firma  NVARCHAR(50)  = N'Staff MuPGA';
DECLARE @motivo NVARCHAR(200) = N'Aviso del staff — si tenés dudas, abrí un hilo nuevo';

IF @autor = 'CAMBIAME'
BEGIN
    RAISERROR('Editá la variable @autor con tu cuenta del sitio antes de correr el script.', 16, 1);
    RETURN;
END;

-- ============================================================
-- SECCIÓN A — Categorías
-- ============================================================
-- sort_order va de 10 en 10 a propósito: así podés intercalar una
-- categoría nueva más adelante sin renumerar todas las demás.

IF NOT EXISTS (SELECT 1 FROM forum.categories WHERE slug = 'anuncios')
    INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post, is_hidden)
    VALUES (N'📢 Anuncios y novedades', 'anuncios',
            N'Actualizaciones del server, eventos oficiales y mantenimientos. Podés responder, pero los hilos los abre el staff.',
            10, 1, 0);

IF NOT EXISTS (SELECT 1 FROM forum.categories WHERE slug = 'general')
    INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post, is_hidden)
    VALUES (N'💬 General', 'general',
            N'Charla del server: dudas rápidas, sugerencias y todo lo que no entre en las otras secciones.',
            20, 0, 0);

IF NOT EXISTS (SELECT 1 FROM forum.categories WHERE slug = 'guias')
    INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post, is_hidden)
    VALUES (N'📖 Guías y builds', 'guias',
            N'Guías de clase, spots, resets, eventos y todo lo que le sirva al que recién arranca.',
            30, 0, 0);

IF NOT EXISTS (SELECT 1 FROM forum.categories WHERE slug = 'guilds')
    INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post, is_hidden)
    VALUES (N'🛡️ Guilds y reclutamiento', 'guilds',
            N'Buscá guild o reclutá para la tuya. Un hilo por guild.',
            40, 0, 0);

IF NOT EXISTS (SELECT 1 FROM forum.categories WHERE slug = 'compra-venta')
    INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post, is_hidden)
    VALUES (N'💰 Compra / venta', 'compra-venta',
            N'Intercambio de ítems y zen entre jugadores. Ojo con las estafas: el staff no media en tratos privados.',
            50, 0, 0);

IF NOT EXISTS (SELECT 1 FROM forum.categories WHERE slug = 'bugs')
    INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post, is_hidden)
    VALUES (N'🐛 Reportes de bugs', 'bugs',
            N'Bugs del juego y del sitio. Problemas con tu cuenta o tus ítems van por Reclamos, no acá.',
            60, 0, 0);

-- Oculta: no aparece en el índice y sus hilos dan 404 a quien no es admin
IF NOT EXISTS (SELECT 1 FROM forum.categories WHERE slug = 'staff')
    INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post, is_hidden)
    VALUES (N'🔒 Staff', 'staff',
            N'Pizarrón interno del staff. No visible para los jugadores.',
            999, 1, 1);

-- ============================================================
-- SECCIÓN B — Avisos fijados (uno por categoría)
-- ============================================================
-- Fijados y cerrados: son avisos, no debates. El staff igual puede
-- responder en hilos cerrados (fix F-08.04), así que se pueden usar
-- para agregar aclaraciones más adelante.
--
-- Si preferís escribirlos vos desde el foro, no corras esta sección:
-- con la A alcanza para tener el foro funcionando.

DECLARE @cat_anuncios INT = (SELECT id FROM forum.categories WHERE slug = 'anuncios');
DECLARE @cat_general  INT = (SELECT id FROM forum.categories WHERE slug = 'general');
DECLARE @cat_guias    INT = (SELECT id FROM forum.categories WHERE slug = 'guias');
DECLARE @cat_guilds   INT = (SELECT id FROM forum.categories WHERE slug = 'guilds');
DECLARE @cat_venta    INT = (SELECT id FROM forum.categories WHERE slug = 'compra-venta');
DECLARE @cat_bugs     INT = (SELECT id FROM forum.categories WHERE slug = 'bugs');
DECLARE @cat_staff    INT = (SELECT id FROM forum.categories WHERE slug = 'staff');

-- ── Anuncios ──────────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM forum.threads WHERE category_id = @cat_anuncios AND title = N'Bienvenido al foro de MuPGA — leé esto antes de publicar')
    INSERT INTO forum.threads (category_id, title, body, author_account, author_display_name, is_pinned, is_locked, locked_reason)
    VALUES (@cat_anuncios, N'Bienvenido al foro de MuPGA — leé esto antes de publicar',
N'Bienvenido al foro oficial de **MuPGA**. Se entra con la **misma cuenta del sitio**: no hace falta registrarse de nuevo.

## Para publicar
- Necesitás tener un **personaje creado** en el server. Para leer no hace falta nada.
- El nombre que se ve en tus mensajes es el de tu **personaje principal**, nunca el de tu cuenta.
- Podés editar lo que escribiste durante los **primeros 30 minutos**. Después, pedíselo al staff.

## Dónde va cada cosa
- Dudas rápidas y charla → **General**
- Guías, builds y spots → **Guías y builds**
- Buscar o armar guild → **Guilds y reclutamiento**
- Vender o cambiar ítems → **Compra / venta**
- Bugs del juego o del sitio → **Reportes de bugs**
- Problemas con **tu cuenta, tus ítems o una compra** → eso no va al foro, se abre en [Reclamos](https://mupga.com.ar/reclamos/)

## Lo que no va
- Publicidad de otros servers.
- Insultos, discriminación y peleas personales.
- El mismo tema abierto en varias categorías.
- Revivir hilos viejos para escribir "up".

## Si ves algo que no corresponde
No le contestes: usá el botón **🚩 Reportar** que está abajo de cada mensaje. Le llega al staff y el autor no se entera de quién lo reportó.

¿Te sirvió un mensaje? Dejale un **🙏 Agradecer** — es la forma de que las buenas guías se noten.',
            @autor, @firma, 1, 1, @motivo);

-- ── General ───────────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM forum.threads WHERE category_id = @cat_general AND title = N'Cómo aprovechar esta sección')
    INSERT INTO forum.threads (category_id, title, body, author_account, author_display_name, is_pinned, is_locked, locked_reason)
    VALUES (@cat_general, N'Cómo aprovechar esta sección',
N'Acá va todo lo que no entra en las otras categorías: dudas rápidas, sugerencias y charla del server.

- **Buscá antes de preguntar.** Hay un [buscador](https://mupga.com.ar/foro/buscar/) arriba de la página: la mayoría de las dudas ya están respondidas.
- **Un tema por hilo.** Si tenés tres preguntas distintas, van tres hilos — así el que busque después encuentra.
- **Título descriptivo.** "No me anda el juego" no dice nada; "Se me cierra al entrar a Kalima" sí.
- Si tu problema es con **tu cuenta o una compra**, no va acá: [abrí un reclamo](https://mupga.com.ar/reclamos/), que es privado y queda registrado.',
            @autor, @firma, 1, 1, @motivo);

-- ── Guías ─────────────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM forum.threads WHERE category_id = @cat_guias AND title = N'Cómo escribir una guía que la gente use')
    INSERT INTO forum.threads (category_id, title, body, author_account, author_display_name, is_pinned, is_locked, locked_reason)
    VALUES (@cat_guias, N'Cómo escribir una guía que la gente use',
N'Esta es la sección que le da valor al foro a largo plazo: lo que escribas acá lo va a leer gente que todavía no entró al server.

## Formato
- Usá **subtítulos** para separar las secciones — se lee muchísimo mejor que un bloque de texto corrido.
- Para listas, empezá la línea con un guion y un espacio.
- **Negrita** para lo importante, sin abusar.
- La barra de arriba del editor tiene todo eso en botones, y **👁 Vista previa** te muestra cómo va a quedar antes de publicar.

## Contenido
- Aclará para qué **clase y nivel o resets** aplica lo que contás.
- Si algo cambia en una actualización, **editá tu guía** en vez de abrir una nueva.
- Las capturas ayudan muchísimo: botón **🖼️** del editor (JPG, PNG o WebP, hasta 5 MB).

Si una guía te sirvió, dejale un **🙏 Agradecer**: es lo que las hace destacar.',
            @autor, @firma, 1, 1, @motivo);

-- ── Guilds ────────────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM forum.threads WHERE category_id = @cat_guilds AND title = N'Cómo publicar tu guild')
    INSERT INTO forum.threads (category_id, title, body, author_account, author_display_name, is_pinned, is_locked, locked_reason)
    VALUES (@cat_guilds, N'Cómo publicar tu guild',
N'**Un hilo por guild.** Si ya tenés el tuyo, **editalo** en vez de abrir uno nuevo — así la sección no se llena de duplicados y siempre se ve la info actualizada.

## Plantilla sugerida
- **Nombre de la guild:**
- **Líder y contacto:**
- **Requisitos:** nivel, resets, si piden Discord
- **Horarios de actividad:**
- **Qué buscan:** gente para Castle Siege, para farmear, para eventos...

Si estás **buscando** guild en vez de reclutar, aclaralo en el título. Por ejemplo: "BUSCO guild activa para CS".

Para coordinar con otra guild o arreglar algo puntual, mejor por mensaje directo o Discord: acá dejemos solo las convocatorias.',
            @autor, @firma, 1, 1, @motivo);

-- ── Compra / venta ────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM forum.threads WHERE category_id = @cat_venta AND title = N'Reglas de compra / venta — leé antes de cerrar un trato')
    INSERT INTO forum.threads (category_id, title, body, author_account, author_display_name, is_pinned, is_locked, locked_reason)
    VALUES (@cat_venta, N'Reglas de compra / venta — leé antes de cerrar un trato',
N'Esta sección es un **tablón de anuncios entre jugadores**. El staff no participa de los tratos.

## Lo más importante
- **El staff no media en tratos entre jugadores** y **no devuelve ítems ni zen** que hayas entregado por tu cuenta. Si te estafan, el ítem no vuelve.
- Hacé el intercambio **dentro del juego**, con los dos personajes presentes. Nada de "mandame vos primero y después te paso".
- Guardá **capturas** de lo que arreglaron: es lo único que sirve después.
- Desconfiá de cuentas nuevas y sin historial en el foro.

## Formato del hilo
- Título claro con lo que ofrecés o buscás. Por ejemplo: "VENDO set +11 de DK" o "BUSCO alas nivel 2".
- Poné el precio o qué aceptás a cambio.
- Cuando cierres el trato, **editá el hilo** y aclaralo así nadie te sigue escribiendo.

## Prohibido
- Vender **cuentas**.
- Vender ítems o zen **por dinero real**. Los WCoins se compran únicamente en la [tienda oficial](https://mupga.com.ar/donate/).

Si alguien te estafa, reportá el mensaje con **🚩** y contá lo que pasó. No abras un hilo para escracharlo: eso se resuelve con el staff, no a los gritos.',
            @autor, @firma, 1, 1, @motivo);

-- ── Bugs ──────────────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM forum.threads WHERE category_id = @cat_bugs AND title = N'Cómo reportar un bug para que se pueda arreglar')
    INSERT INTO forum.threads (category_id, title, body, author_account, author_display_name, is_pinned, is_locked, locked_reason)
    VALUES (@cat_bugs, N'Cómo reportar un bug para que se pueda arreglar',
N'Primero lo importante: **si perdiste ítems, zen o el acceso a tu cuenta, eso no va acá.** Se resuelve en [Reclamos](https://mupga.com.ar/reclamos/), que es privado y queda registrado.

Esta sección es para **bugs reproducibles** del juego o del sitio, para que otros los confirmen y el staff los pueda ubicar.

## Qué poner en el reporte
- **Qué pasó**, en una línea.
- **Cómo repetirlo**, paso por paso. Un bug que no se puede repetir no se puede arreglar.
- **Dónde y cuándo**: mapa, personaje, día y hora aproximada.
- **Captura o video** si tenés (botón 🖼️ del editor).

## Antes de publicar
Fijate con el [buscador](https://mupga.com.ar/foro/buscar/) si ya está reportado. Si está, sumate a ese hilo con tu caso en vez de abrir otro: cuantos más confirmen el mismo bug, más fácil es encontrarlo.

No pidas compensación acá — para eso está Reclamos.',
            @autor, @firma, 1, 1, @motivo);

-- ── Staff (oculta) ────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM forum.threads WHERE category_id = @cat_staff AND title = N'Pizarrón interno del staff')
    INSERT INTO forum.threads (category_id, title, body, author_account, author_display_name, is_pinned, is_locked, locked_reason)
    VALUES (@cat_staff, N'Pizarrón interno del staff',
N'Esta categoría está marcada como **oculta**: no aparece en el índice del foro y, si alguien entra por link directo a un hilo de acá, recibe un 404 — ni se entera de que existe.

Sirve para lo que en Discord se pierde a los dos días: decisiones de moderación y su porqué, casos que quedaron pendientes, borradores de anuncios antes de publicarlos.

Recordá que **el log de moderación del ControlPanel es la fuente de verdad** de las acciones (quién editó, borró, cerró o baneó qué, y cuándo). Esto es para el contexto que el log no guarda.',
            @autor, @firma, 1, 1, @motivo);

-- ============================================================
-- Verificación final
-- ============================================================
SELECT c.sort_order AS [Orden], c.name AS [Categoría], c.slug AS [Slug],
       c.admin_only_post AS [SoloStaff], c.is_hidden AS [Oculta],
       (SELECT COUNT(*) FROM forum.threads t WHERE t.category_id = c.id) AS [Hilos]
FROM forum.categories c
ORDER BY c.sort_order, c.name;
GO
