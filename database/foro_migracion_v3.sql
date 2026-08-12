-- ============================================================
-- MuPGA Foro — Migración v3 (Etapas 4-6 del backlog)
-- Ejecutar como sa en mupga_admin, DESPUÉS de foro_migracion_v2.sql.
--
-- Aditiva e idempotente: solo CREATE IF NOT EXISTS, ningún DROP
-- sobre datos existentes. Re-ejecutable sin errores.
--
-- Cubre: seguir hilo (F-07.01), notificaciones (F-07.02),
-- cuota de subida de imágenes (F-04.05). La paginación y la
-- búsqueda (F-03.05/F-10.02/F-11.01) no necesitan schema nuevo —
-- usan los índices de la v2.
--
-- Timestamps: SYSUTCDATETIME()/GETUTCDATE() — regla del proyecto
-- (CLAUDE.md, incidente 2026-07-19): nunca offset hardcodeado.
-- ============================================================

USE mupga_admin;
GO

-- ── forum.thread_follows — quién sigue qué hilo (F-07.01) ─────
IF OBJECT_ID('forum.thread_follows', 'U') IS NULL
BEGIN
    CREATE TABLE forum.thread_follows (
        account    VARCHAR(10) NOT NULL,
        thread_id  INT         NOT NULL,
        created_at DATETIME2   NOT NULL DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_forum_thread_follows PRIMARY KEY (account, thread_id),
        CONSTRAINT fk_forum_follows_thread FOREIGN KEY (thread_id)
            REFERENCES forum.threads (id) ON DELETE CASCADE
    );
    CREATE INDEX IX_forum_follows_thread ON forum.thread_follows (thread_id);
END;
GO

-- ── forum.notifications — centro de notificaciones (F-07.02) ──
-- type: 'respuesta' (hilo seguido, agrupada: máx 1 sin leer por hilo)
--       | 'mencion' | 'gracias' | 'moderacion'
-- Purga: avisos de más de 60 días se borran oportunistamente al
-- listar (no hay SQL Agent en Express — nada de jobs).
IF OBJECT_ID('forum.notifications', 'U') IS NULL
BEGIN
    CREATE TABLE forum.notifications (
        id            INT           NOT NULL IDENTITY(1,1),
        account       VARCHAR(10)   NOT NULL,   -- destinatario
        type          VARCHAR(20)   NOT NULL,
        thread_id     INT           NULL,
        post_id       INT           NULL,
        actor_display NVARCHAR(50)  NULL,       -- personaje del que originó el aviso (nunca la cuenta)
        created_at    DATETIME2     NOT NULL DEFAULT SYSUTCDATETIME(),
        read_at       DATETIME2     NULL,
        CONSTRAINT pk_forum_notifications PRIMARY KEY (id)
    );
    CREATE INDEX IX_forum_notifications_account ON forum.notifications (account, read_at, created_at DESC);
END;
GO

-- ── forum.image_uploads — cuota de subida a R2 (F-04.05) ──────
-- Una fila por presigned URL emitida (se cuente o no el PUT final:
-- la cuota es por intención de subida, no hace falta "finalize").
IF OBJECT_ID('forum.image_uploads', 'U') IS NULL
BEGIN
    CREATE TABLE forum.image_uploads (
        id         INT          NOT NULL IDENTITY(1,1),
        account    VARCHAR(10)  NOT NULL,
        object_key VARCHAR(200) NOT NULL,
        created_at DATETIME2    NOT NULL DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_forum_image_uploads PRIMARY KEY (id)
    );
    CREATE INDEX IX_forum_image_uploads_account ON forum.image_uploads (account, created_at);
END;
GO

-- ── Verificación final ───────────────────────────────────────
SELECT s.name AS [Schema], t.name AS [Tabla]
FROM sys.tables t JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'forum' ORDER BY t.name;
GO
