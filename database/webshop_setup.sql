-- ============================================================
-- MuPGA Tienda WCoin — Setup de schema, usuario y tablas
-- Ejecutar como sa (o DBA con permisos suficientes) en MuOnline
--
-- webshop_user: CONTROL solo sobre el schema webshop — lo usa
-- tienda_import.php (ver src/config/webshop_db.php) para reimportar
-- el catálogo. NO tiene acceso a CashShopData ni otras tablas del juego.
--
-- La compra (buy.php) sí necesita ser atómica con CashShopData/
-- CashShopInventory en una sola transacción, así que usa la conexión
-- principal (Database::get(), login de DB_USER en el .env) — por eso
-- el Bloque C le da a ESE login solo SELECT sobre webshop (para leer
-- el precio), nunca escritura.
--
-- ANTES DE EJECUTAR:
--   Reemplazar {{WEBSHOP_DB_PASSWORD}} con la contraseña elegida
--   Reemplazar {{DB_USER}} por el login real usado en DB_USER (.env)
--
-- El script es re-ejecutable sin errores (usa IF NOT EXISTS / OBJECT_ID).
-- ============================================================

-- ── Bloque A — Login, usuario y schema ───────────────────────
IF NOT EXISTS (
    SELECT 1 FROM sys.server_principals WHERE name = 'webshop_user'
)
BEGIN
    CREATE LOGIN webshop_user WITH PASSWORD = '{{WEBSHOP_DB_PASSWORD}}';
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.database_principals WHERE name = 'webshop_user'
)
BEGIN
    CREATE USER webshop_user FOR LOGIN webshop_user;
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.schemas WHERE name = 'webshop'
)
BEGIN
    EXEC('CREATE SCHEMA webshop AUTHORIZATION webshop_user');
END;

GRANT CONTROL ON SCHEMA::webshop TO webshop_user;

-- ── Bloque C — Lectura para la compra (buy.php, vía Database::get()) ──
GRANT SELECT ON SCHEMA::webshop TO {{DB_USER}};

-- ── Bloque D — Tablas ────────────────────────────────────────

-- Categorías del cash shop (de Client/IBSCategory.txt)
IF OBJECT_ID('webshop.categories', 'U') IS NULL
BEGIN
    CREATE TABLE webshop.categories (
        category_id INT          NOT NULL,
        name        VARCHAR(100) NOT NULL,
        CONSTRAINT pk_webshop_categories PRIMARY KEY (category_id)
    );
END;

-- Catálogo de productos — reconstruido completo en cada import
-- (ver src/public/api/admin/tienda_import.php)
IF OBJECT_ID('webshop.products', 'U') IS NULL
BEGIN
    CREATE TABLE webshop.products (
        id                 INT          NOT NULL IDENTITY(1,1),
        product_base_index INT          NOT NULL,
        product_main_index INT          NOT NULL,
        category_id        INT          NULL,
        package_main_index INT          NULL,
        name               VARCHAR(200) NOT NULL,
        description        VARCHAR(1000) NULL,
        price_wcoin        INT          NOT NULL,
        item_id            INT          NOT NULL,
        item_level         SMALLINT     NOT NULL DEFAULT 0,
        item_skill         SMALLINT     NOT NULL DEFAULT 0,
        item_luck          SMALLINT     NOT NULL DEFAULT 0,
        item_option        SMALLINT     NOT NULL DEFAULT 0,
        item_exc_opt       SMALLINT     NOT NULL DEFAULT 0,
        item_set_opt       SMALLINT     NOT NULL DEFAULT 0,
        item_joh           SMALLINT     NOT NULL DEFAULT 0,
        item_oex           SMALLINT     NOT NULL DEFAULT 0,
        item_socket1       SMALLINT     NOT NULL DEFAULT 255,
        item_socket2       SMALLINT     NOT NULL DEFAULT 255,
        item_socket3       SMALLINT     NOT NULL DEFAULT 255,
        item_socket4       SMALLINT     NOT NULL DEFAULT 255,
        item_socket5       SMALLINT     NOT NULL DEFAULT 255,
        duration_seconds   INT          NOT NULL DEFAULT 0,
        icon_group         TINYINT      NOT NULL,
        icon_index         SMALLINT     NOT NULL,
        has_icon           BIT          NOT NULL DEFAULT 1,
        active             BIT          NOT NULL DEFAULT 1,
        imported_at        DATETIME     NOT NULL DEFAULT GETDATE(),
        CONSTRAINT pk_webshop_products PRIMARY KEY (id),
        CONSTRAINT uq_webshop_products_variant UNIQUE (product_base_index, product_main_index)
    );
END;

-- ── Verificación final ───────────────────────────────────────
SELECT
    s.name  AS [Schema],
    t.name  AS [Tabla],
    t.type_desc
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'webshop'
ORDER BY t.name;


