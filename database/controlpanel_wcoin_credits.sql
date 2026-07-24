-- ============================================================
-- ControlPanel — Acreditación manual de WCoin (auditoría propia)
-- Correr en SSMS contra la base mupga_admin (con usuario admin, no
-- mupga_web_svc — este login no tiene DDL, ver admin_db.php).
--
-- Esta tabla NO es el log del juego (eso ya lo hace sp_AddWCoinWithLog
-- en CashLog, dentro de la base del juego). Es la auditoría propia del
-- ControlPanel: qué admin acreditó, a qué cuenta, cuánto y por qué.
-- mupga_web_svc ya tiene db_datareader + db_datawriter sobre toda
-- mupga_admin (admin_db.php), así que no hace falta ningún GRANT extra.
-- ============================================================
USE mupga_admin;
GO

IF OBJECT_ID('dbo.wcoin_credits', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.wcoin_credits (
        id             INT           NOT NULL IDENTITY(1,1),
        admin_id       NVARCHAR(10)  NOT NULL,  -- memb___id del admin que acreditó
        target_account NVARCHAR(10)  NOT NULL,  -- memb___id de la cuenta acreditada
        amount         INT           NOT NULL,
        reason         NVARCHAR(300) NULL,
        created_at     DATETIME2     NOT NULL,  -- seteado explícito en PHP con GETUTCDATE(), sin DEFAULT
        CONSTRAINT pk_wcoin_credits PRIMARY KEY (id)
    );
    PRINT 'dbo.wcoin_credits creada.';
END
ELSE
    PRINT 'dbo.wcoin_credits ya existe, no se toca.';
GO

-- Verificación
SELECT TOP 10 * FROM dbo.wcoin_credits ORDER BY id DESC;
GO
