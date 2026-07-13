-- ============================================================
-- ControlPanel — Etapa 4: alta de admin + presets de status
-- Correr en SSMS contra la base mupga_admin (con usuario admin).
-- ⚠ REEMPLAZAR 'TU_CUENTA' por el memb___id real de la cuenta admin
--   (máx 10 caracteres, el mismo usuario con el que te logueás al sitio).
-- ============================================================
USE mupga_admin;
GO

-- ── Admin inicial ──
IF NOT EXISTS (SELECT 1 FROM dbo.admins WHERE memb___id = 'TU_CUENTA')
    INSERT INTO dbo.admins (memb___id, role, active, added_by, added_at)
    VALUES ('TU_CUENTA', 'owner', 1, 'seed', GETDATE());
GO

-- ── Presets de status (avisos típicos listos para aplicar) ──
IF NOT EXISTS (SELECT 1 FROM dbo.status_presets WHERE preset_key = 'mantenimiento')
    INSERT INTO dbo.status_presets (preset_key, title, message)
    VALUES ('mantenimiento', N'Mantenimiento programado',
            N'Estamos realizando tareas de mantenimiento. El servidor puede presentar cortes breves. Gracias por la paciencia.');

IF NOT EXISTS (SELECT 1 FROM dbo.status_presets WHERE preset_key = 'reinicio')
    INSERT INTO dbo.status_presets (preset_key, title, message)
    VALUES ('reinicio', N'Reinicio de servidor en curso',
            N'El servidor se está reiniciando. Volvé a intentar conectarte en unos minutos.');

IF NOT EXISTS (SELECT 1 FROM dbo.status_presets WHERE preset_key = 'caida')
    INSERT INTO dbo.status_presets (preset_key, title, message)
    VALUES ('caida', N'Servidor temporalmente fuera de línea',
            N'Estamos trabajando para restablecer el servicio lo antes posible. Seguinos en Discord para novedades.');
GO

-- Verificación
SELECT id, memb___id, role, active FROM dbo.admins;
SELECT id, preset_key, title FROM dbo.status_presets;
