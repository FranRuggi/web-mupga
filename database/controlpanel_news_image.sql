-- ============================================================
-- ControlPanel — columna de imagen en news
-- Correr en SSMS contra mupga_admin (con usuario admin — el
-- login mupga_web_svc no tiene permisos de DDL, es intencional).
-- ============================================================
USE mupga_admin;
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_NAME = 'news' AND COLUMN_NAME = 'image_url')
    ALTER TABLE dbo.news ADD image_url nvarchar(500) NULL;
GO

SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'news';
