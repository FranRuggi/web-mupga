-- ============================================================
-- MuPGA Foro — Migración v4 (distintivos de autor, F-06.03)
-- Ejecutar como sa en mupga_admin, DESPUÉS de foro_migracion_v3.sql.
--
-- Aditiva e idempotente: una sola tabla nueva, ningún DROP.
--
-- Por qué una tabla de caché y no una consulta en cada render:
-- el distintivo de staff sale de dbo.admins, que vive en ESTA misma
-- base y es gratis de leer. El de VIP sale de MEMB_INFO, que está en
-- la base del juego — y la regla del proyecto (BACKLOG_FORO F-06.03 y
-- F-13.04) es que el foro no le pegue a la base de juego en cada
-- render. Esta tabla guarda el resultado por cuenta con un TTL corto:
-- se refresca solo cuando venció, y solo para los autores que están
-- en pantalla. Si la base del juego no responde, se usa lo último
-- cacheado y el mensaje se muestra igual, sin distintivo.
--
-- No guarda nada sensible: cuenta, si tiene VIP y hasta cuándo.
-- ============================================================

USE mupga_admin;
GO

IF OBJECT_ID('forum.author_badges', 'U') IS NULL
BEGIN
    CREATE TABLE forum.author_badges (
        account    VARCHAR(10) NOT NULL,
        is_vip     BIT         NOT NULL CONSTRAINT df_forum_badges_vip DEFAULT 0,
        vip_until  DATETIME2   NULL,   -- se re-evalúa contra ahora en cada lectura,
                                       -- así un VIP que vence dentro del TTL deja de
                                       -- mostrarse sin esperar al refresco
        checked_at DATETIME2   NOT NULL CONSTRAINT df_forum_badges_checked DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_forum_author_badges PRIMARY KEY (account)
    );
END;
GO

-- ── Verificación final ───────────────────────────────────────
SELECT s.name AS [Schema], t.name AS [Tabla]
FROM sys.tables t JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'forum' ORDER BY t.name;
GO
