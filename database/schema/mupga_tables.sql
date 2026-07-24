-- ============================================================
-- Tablas propias del sitio MuPGA (no pertenecen al GameServer ni a WebEngine).
-- Ejecutar una sola vez en la base MuOnline del VPS.
-- ============================================================

USE MuOnline;
GO

-- ── Paso 1: Crear tabla con constraint nombrado explícitamente ──
-- IMPORTANTE: usar CONSTRAINT nombrado para evitar conflictos al recriar.
-- Si la tabla ya existe con PK autogenerada, hacer DROP primero (Paso 1b).
CREATE TABLE MUPGA_ACCOUNT_COUNTRY (
    Username     VARCHAR(10)  NOT NULL,
    CountryCode  CHAR(2)      NOT NULL,
    RegisteredAt DATETIME     NOT NULL DEFAULT GETDATE(),
    CONSTRAINT PK_MUPGA_ACCOUNT_COUNTRY PRIMARY KEY (Username)
);
GO

-- ── Paso 1b: Si ya existe con PK rota, recrear limpio ──────────
-- DROP TABLE IF EXISTS MUPGA_ACCOUNT_COUNTRY;
-- GO
-- (luego volver a correr el CREATE TABLE de arriba)

-- ── Paso 2: Migración única desde WebEngine ─────────────────────
-- Leer países existentes de WebEngine y cargarlos en la tabla propia.
-- WEBENGINE_ACCOUNT_COUNTRY puede tener filas duplicadas por cuenta,
-- por eso se usa GROUP BY + MIN() para tomar uno solo por usuario.
-- Ejecutar solo si MUPGA_ACCOUNT_COUNTRY está vacía.
--
-- INSERT INTO MUPGA_ACCOUNT_COUNTRY (Username, CountryCode, RegisteredAt)
-- SELECT
--     wac.account,
--     UPPER(LEFT(RTRIM(MIN(wac.country)), 2)),
--     GETDATE()
-- FROM WEBENGINE_ACCOUNT_COUNTRY wac
-- WHERE LEN(RTRIM(wac.country)) = 2
--   AND EXISTS (SELECT 1 FROM MEMB_INFO m WHERE m.memb___id = wac.account)
-- GROUP BY wac.account;
-- GO

-- ── Verificar duplicados en WebEngine (diagnóstico) ─────────────
-- SELECT account, COUNT(*) cant
-- FROM WEBENGINE_ACCOUNT_COUNTRY
-- GROUP BY account HAVING COUNT(*) > 1;

-- ── Verificar resultado final ───────────────────────────────────
-- SELECT Username, CountryCode FROM MUPGA_ACCOUNT_COUNTRY ORDER BY Username;
