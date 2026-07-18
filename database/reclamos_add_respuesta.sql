-- ============================================================
-- MuPGA Reclamos — Agrega respuesta del admin + flag de lectura
-- Ejecutar como sa (o DBA) contra la base mupga_admin.
-- Re-ejecutable (chequea existencia de cada columna antes de agregarla).
--
-- Flujo: el admin responde desde /controlpanel/ (usa mupga_web_svc, que
-- ya tiene db_datawriter sobre TODA mupga_admin — no hace falta ningún
-- grant nuevo para el schema reclamos). El jugador ve la respuesta como
-- banner en cualquier página del sitio hasta que la marca como leída.
-- ============================================================
USE mupga_admin;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('reclamos.reclamos') AND name = 'respuesta'
)
BEGIN
    ALTER TABLE reclamos.reclamos ADD respuesta NVARCHAR(MAX) NULL;
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('reclamos.reclamos') AND name = 'respondido_en'
)
BEGIN
    ALTER TABLE reclamos.reclamos ADD respondido_en DATETIME2 NULL;
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('reclamos.reclamos') AND name = 'respondido_por'
)
BEGIN
    -- memb___id de quien respondió (nvarchar(10) — mismo largo que updated_by
    -- en el resto de mupga_admin, los usernames del juego son de máx 10).
    ALTER TABLE reclamos.reclamos ADD respondido_por NVARCHAR(10) NULL;
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('reclamos.reclamos') AND name = 'leido'
)
BEGIN
    ALTER TABLE reclamos.reclamos ADD leido BIT NOT NULL DEFAULT 0;
END;
GO

-- Verificación
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'reclamos' AND TABLE_NAME = 'reclamos'
ORDER BY ORDINAL_POSITION;
GO
