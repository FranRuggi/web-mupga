-- ============================================================
-- MuPGA Foro — Migración v2 (hardening + moderación)
-- Ejecutar como sa en mupga_admin, DESPUÉS de foro_setup.sql.
--
-- Aditiva e idempotente: solo ALTER ADD / CREATE IF NOT EXISTS,
-- ningún DROP sobre datos existentes. Re-ejecutable sin errores.
--
-- Cubre: soft delete (F-02.03/F-03.04), ventana de edición con
-- marca de staff (F-02.02), motivo de cierre (F-08.04), ban con
-- vencimiento (F-08.06), reportes (F-08.02/03), log de auditoría
-- (F-08.05), categorías ocultas (F-13.01) e índices (F-13.04).
--
-- Timestamps: SYSUTCDATETIME()/GETUTCDATE() — regla del proyecto
-- (CLAUDE.md, incidente 2026-07-19): nunca offset hardcodeado.
-- ============================================================

USE mupga_admin;
GO

-- ── forum.threads — soft delete, marca de staff, motivo de cierre ──
IF COL_LENGTH('forum.threads', 'deleted_at') IS NULL
    ALTER TABLE forum.threads ADD deleted_at DATETIME2 NULL;
IF COL_LENGTH('forum.threads', 'deleted_by') IS NULL
    ALTER TABLE forum.threads ADD deleted_by VARCHAR(10) NULL;
IF COL_LENGTH('forum.threads', 'edited_by_staff') IS NULL
    ALTER TABLE forum.threads ADD edited_by_staff BIT NOT NULL CONSTRAINT df_forum_threads_ebs DEFAULT 0;
IF COL_LENGTH('forum.threads', 'locked_reason') IS NULL
    ALTER TABLE forum.threads ADD locked_reason NVARCHAR(200) NULL;
GO

-- ── forum.posts — soft delete, marca de staff ─────────────────
IF COL_LENGTH('forum.posts', 'deleted_at') IS NULL
    ALTER TABLE forum.posts ADD deleted_at DATETIME2 NULL;
IF COL_LENGTH('forum.posts', 'deleted_by') IS NULL
    ALTER TABLE forum.posts ADD deleted_by VARCHAR(10) NULL;
IF COL_LENGTH('forum.posts', 'edited_by_staff') IS NULL
    ALTER TABLE forum.posts ADD edited_by_staff BIT NOT NULL CONSTRAINT df_forum_posts_ebs DEFAULT 0;
GO

-- ── forum.banned_accounts — vencimiento opcional ──────────────
IF COL_LENGTH('forum.banned_accounts', 'expires_at') IS NULL
    ALTER TABLE forum.banned_accounts ADD expires_at DATETIME2 NULL;
GO

-- ── forum.categories — ocultar categoría ──────────────────────
IF COL_LENGTH('forum.categories', 'is_hidden') IS NULL
    ALTER TABLE forum.categories ADD is_hidden BIT NOT NULL CONSTRAINT df_forum_categories_hidden DEFAULT 0;
GO

-- ── forum.reports — reportes de contenido ─────────────────────
IF OBJECT_ID('forum.reports', 'U') IS NULL
BEGIN
    CREATE TABLE forum.reports (
        id               INT            NOT NULL IDENTITY(1,1),
        target_type      VARCHAR(10)    NOT NULL,   -- 'thread' | 'post'
        target_id        INT            NOT NULL,
        reporter_account VARCHAR(10)    NOT NULL,
        reason_code      VARCHAR(30)    NOT NULL,   -- allowlist en ForumValidation
        comment          NVARCHAR(500)  NULL,
        status           VARCHAR(20)    NOT NULL DEFAULT 'pendiente', -- pendiente | resuelto_accion | resuelto_sin_merito
        resolved_by      VARCHAR(10)    NULL,
        resolved_note    NVARCHAR(300)  NULL,
        created_at       DATETIME2      NOT NULL DEFAULT SYSUTCDATETIME(),
        resolved_at      DATETIME2      NULL,
        CONSTRAINT pk_forum_reports PRIMARY KEY (id),
        CONSTRAINT uq_forum_reports UNIQUE (target_type, target_id, reporter_account)
    );
END;
GO

-- ── forum.moderation_log — auditoría (sin UI de borrado, a propósito) ──
IF OBJECT_ID('forum.moderation_log', 'U') IS NULL
BEGIN
    CREATE TABLE forum.moderation_log (
        id            INT            NOT NULL IDENTITY(1,1),
        actor_account VARCHAR(10)    NOT NULL,
        action        VARCHAR(30)    NOT NULL,   -- edit_thread/edit_post/delete_thread/delete_post/restore_*/pin/unpin/lock/unlock/move/ban/unban/resolve_report
        target_type   VARCHAR(10)    NOT NULL,   -- 'thread' | 'post' | 'account' | 'report'
        target_id     NVARCHAR(50)   NOT NULL,   -- id numérico o cuenta (bans)
        reason        NVARCHAR(300)  NULL,
        body_before   NVARCHAR(MAX)  NULL,       -- cuerpo previo en ediciones/borrados de staff
        created_at    DATETIME2      NOT NULL DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_forum_moderation_log PRIMARY KEY (id)
    );
END;
GO

-- ── Índices (F-13.04): los FK no crean índice automático en SQL Server ──
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_forum_threads_category_activity' AND object_id = OBJECT_ID('forum.threads'))
    CREATE INDEX IX_forum_threads_category_activity ON forum.threads (category_id, last_post_at DESC) INCLUDE (deleted_at);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_forum_posts_thread_created' AND object_id = OBJECT_ID('forum.posts'))
    CREATE INDEX IX_forum_posts_thread_created ON forum.posts (thread_id, created_at);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_forum_threads_author_created' AND object_id = OBJECT_ID('forum.threads'))
    CREATE INDEX IX_forum_threads_author_created ON forum.threads (author_account, created_at);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_forum_posts_author_created' AND object_id = OBJECT_ID('forum.posts'))
    CREATE INDEX IX_forum_posts_author_created ON forum.posts (author_account, created_at);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_forum_reports_status' AND object_id = OBJECT_ID('forum.reports'))
    CREATE INDEX IX_forum_reports_status ON forum.reports (status, created_at);
GO

-- ── Verificación final ───────────────────────────────────────
SELECT s.name AS [Schema], t.name AS [Tabla]
FROM sys.tables t JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'forum' ORDER BY t.name;
GO
