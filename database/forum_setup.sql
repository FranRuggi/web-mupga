-- ============================================================
-- MuPGA Foro (phpBB) — Setup de base, login y permisos
-- Ejecutar como sa (o DBA con permisos suficientes) en el SQL Server Express del VPS
--
-- A diferencia de Prode/Tienda/ControlPanel (que usan un schema propio DENTRO de
-- una base compartida), el foro va en una base TOTALMENTE APARTE (mupga_forum):
-- phpBB trae su propio instalador web que crea ~70 tablas la primera vez y las
-- altera en cada actualización — necesita permisos de DDL amplios (db_owner)
-- que NO queremos dar sobre MuOnline ni sobre mupga_admin. Aislado en su propia
-- base, un db_owner ahí adentro no puede tocar nada del juego ni del ControlPanel.
--
-- ANTES DE EJECUTAR:
--   Reemplazar {{FORUM_DB_PASSWORD}} con la contraseña elegida
--
-- El script es re-ejecutable sin errores (usa IF NOT EXISTS).
-- ============================================================

-- ── Bloque A — Base de datos dedicada ────────────────────────
IF NOT EXISTS (SELECT 1 FROM sys.databases WHERE name = 'mupga_forum')
BEGIN
    CREATE DATABASE mupga_forum;
END;
GO

-- ── Bloque B — Login y usuario ───────────────────────────────
IF NOT EXISTS (SELECT 1 FROM sys.server_principals WHERE name = 'forum_admin')
BEGIN
    CREATE LOGIN forum_admin WITH PASSWORD = '{{FORUM_DB_PASSWORD}}';
END;
GO

USE mupga_forum;
GO

IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name = 'forum_admin')
BEGIN
    CREATE USER forum_admin FOR LOGIN forum_admin;
END;
GO

-- db_owner SOLO dentro de mupga_forum — el instalador de phpBB (y sus updates)
-- necesitan crear/alterar tablas e índices libremente. Esta base no tiene
-- ninguna relación con MuOnline ni mupga_admin, así que el radio de acción
-- de este login queda 100% contenido acá adentro.
ALTER ROLE db_owner ADD MEMBER forum_admin;
GO

-- ── Verificación final ───────────────────────────────────────
SELECT name AS [Login] FROM sys.server_principals WHERE name = 'forum_admin';
SELECT name AS [Base]  FROM sys.databases         WHERE name = 'mupga_forum';
