-- ============================================================
-- Tablas propias del sitio MuPGA (no pertenecen al GameServer ni a WebEngine).
-- Ejecutar una sola vez en la base MuOnline del VPS.
-- ============================================================

USE MuOnline;
GO

-- País detectado al momento del registro vía ip-api.com
CREATE TABLE MUPGA_ACCOUNT_COUNTRY (
    Username     VARCHAR(10)  NOT NULL PRIMARY KEY,
    CountryCode  CHAR(2)      NOT NULL,
    RegisteredAt DATETIME     NOT NULL DEFAULT GETDATE()
);
GO
