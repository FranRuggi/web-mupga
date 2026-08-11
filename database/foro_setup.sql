-- ============================================================
-- MuPGA Foro (módulo nativo) — Setup de schema, tablas y login
-- Ejecutar como sa (o DBA con permisos suficientes) en mupga_admin.
--
-- No confundir con database/forum_setup.sql (ese es de la instalación de
-- phpBB, base separada `mupga_forum`, parqueada sin usar). Este script es
-- el del foro nuevo, nativo del sitio, mismo patrón que reclamos_setup.sql:
-- schema propio (`forum`) dentro de `mupga_admin`, login propio de mínimo
-- privilegio dueño solo de ese schema.
--
-- ANTES DE EJECUTAR:
--   Reemplazar 'CAMBIAR_ESTA_PASSWORD' con la contraseña elegida
--
-- El script es re-ejecutable sin errores (usa IF NOT EXISTS).
-- ============================================================

-- ── Bloque A — Login a nivel de servidor ──────────────────────
IF NOT EXISTS (
    SELECT 1 FROM sys.server_principals WHERE name = 'mupga_forum_svc'
)
BEGIN
    CREATE LOGIN mupga_forum_svc WITH PASSWORD = 'CAMBIAR_ESTA_PASSWORD';
END;
GO

-- ── Bloque B — Base mupga_admin: schema forum ─────────────────
USE mupga_admin;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.database_principals WHERE name = 'mupga_forum_svc'
)
BEGIN
    CREATE USER mupga_forum_svc FOR LOGIN mupga_forum_svc;
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.schemas WHERE name = 'forum'
)
BEGIN
    EXEC('CREATE SCHEMA forum AUTHORIZATION mupga_forum_svc');
END;
GO

-- ── Bloque C — Tablas ──────────────────────────────────────────

IF OBJECT_ID('forum.categories', 'U') IS NULL
BEGIN
    CREATE TABLE forum.categories (
        id              INT             NOT NULL IDENTITY(1,1),
        name            NVARCHAR(100)   NOT NULL,
        slug            NVARCHAR(100)   NOT NULL,
        description     NVARCHAR(300)   NULL,
        sort_order      INT             NOT NULL DEFAULT 0,
        admin_only_post BIT             NOT NULL DEFAULT 0,
        created_at      DATETIME2       NOT NULL DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_forum_categories PRIMARY KEY (id),
        CONSTRAINT uq_forum_categories_slug UNIQUE (slug)
    );
END;
GO

IF OBJECT_ID('forum.threads', 'U') IS NULL
BEGIN
    CREATE TABLE forum.threads (
        id                   INT             NOT NULL IDENTITY(1,1),
        category_id          INT             NOT NULL,
        title                NVARCHAR(200)   NOT NULL,
        body                 NVARCHAR(MAX)   NOT NULL,
        author_account       VARCHAR(10)     NOT NULL,
        author_display_name  NVARCHAR(50)    NOT NULL,
        is_pinned            BIT             NOT NULL DEFAULT 0,
        is_locked            BIT             NOT NULL DEFAULT 0,
        reply_count          INT             NOT NULL DEFAULT 0,
        created_at           DATETIME2       NOT NULL DEFAULT SYSUTCDATETIME(),
        edited_at            DATETIME2       NULL,
        last_post_at         DATETIME2       NOT NULL DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_forum_threads PRIMARY KEY (id),
        CONSTRAINT fk_forum_threads_category FOREIGN KEY (category_id)
            REFERENCES forum.categories(id)
    );
END;
GO

IF OBJECT_ID('forum.posts', 'U') IS NULL
BEGIN
    CREATE TABLE forum.posts (
        id                   INT             NOT NULL IDENTITY(1,1),
        thread_id            INT             NOT NULL,
        author_account       VARCHAR(10)     NOT NULL,
        author_display_name  NVARCHAR(50)    NOT NULL,
        body                 NVARCHAR(MAX)   NOT NULL,
        created_at           DATETIME2       NOT NULL DEFAULT SYSUTCDATETIME(),
        edited_at            DATETIME2       NULL,
        CONSTRAINT pk_forum_posts PRIMARY KEY (id),
        CONSTRAINT fk_forum_posts_thread FOREIGN KEY (thread_id)
            REFERENCES forum.threads(id) ON DELETE CASCADE
    );
END;
GO

-- target_type: 'thread' (el mensaje de apertura) o 'post' (una respuesta).
-- Un solo tipo de reacción en v1 ("Agradecer"), toggle on/off por cuenta.
IF OBJECT_ID('forum.reactions', 'U') IS NULL
BEGIN
    CREATE TABLE forum.reactions (
        id           INT             NOT NULL IDENTITY(1,1),
        target_type  VARCHAR(10)     NOT NULL,
        target_id    INT             NOT NULL,
        account      VARCHAR(10)     NOT NULL,
        created_at   DATETIME2       NOT NULL DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_forum_reactions PRIMARY KEY (id),
        CONSTRAINT uq_forum_reactions UNIQUE (target_type, target_id, account)
    );
END;
GO

-- Ban acotado al foro únicamente — nunca toca MEMB_INFO.bloc_code (eso
-- bloquearía la cuenta del juego entero, no es lo que pide esto).
IF OBJECT_ID('forum.banned_accounts', 'U') IS NULL
BEGIN
    CREATE TABLE forum.banned_accounts (
        account     VARCHAR(10)     NOT NULL,
        banned_by   VARCHAR(10)     NOT NULL,
        reason      NVARCHAR(300)   NULL,
        banned_at   DATETIME2       NOT NULL DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_forum_banned_accounts PRIMARY KEY (account)
    );
END;
GO

-- ── Verificación final ───────────────────────────────────────
SELECT s.name AS [Schema], t.name AS [Tabla]
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'forum'
ORDER BY t.name;
GO
