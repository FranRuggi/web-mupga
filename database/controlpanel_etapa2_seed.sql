-- ============================================================
-- ControlPanel — Etapa 2: seed inicial de news
-- Correr en SSMS contra la base mupga_admin (con usuario admin).
-- Re-ejecutable: no duplica si las filas ya existen (match por title).
-- Nota: updated_by/created_by es nvarchar(10) — usar valores cortos.
-- ============================================================
USE mupga_admin;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.news WHERE title = N'¡Bienvenidos a MuPGA Season 6!')
    INSERT INTO dbo.news (title, body, category, summary, is_published, published_at, created_by)
    VALUES (
        N'¡Bienvenidos a MuPGA Season 6!',
        N'Después de meses de preparación, MuPGA Season 6 está en línea. Podés registrar tu cuenta desde esta misma página, descargar el cliente en la sección Descargas y conectarte al servidor. ¡Nos vemos en el juego!',
        N'Anuncio',
        N'El servidor está oficialmente en línea. Registrate, descargá el cliente y entrá a jugar.',
        1, '2026-06-01', 'seed'
    );

IF NOT EXISTS (SELECT 1 FROM dbo.news WHERE title = N'Rates del servidor')
    INSERT INTO dbo.news (title, body, category, summary, is_published, published_at, created_by)
    VALUES (
        N'Rates del servidor',
        N'Los rates actuales son: EXP 50x, Drop 30%, Zen 3x. Consultá la página Info del servidor para el listado completo de configuraciones, comandos disponibles y eventos activos.',
        N'Info',
        N'EXP, Drop y demás configuraciones del servidor para que sepas qué esperar.',
        1, '2026-06-01', 'seed'
    );

IF NOT EXISTS (SELECT 1 FROM dbo.news WHERE title = N'Mantenimiento programado')
    INSERT INTO dbo.news (title, body, category, summary, is_published, published_at, created_by)
    VALUES (
        N'Mantenimiento programado',
        N'Se realizará un mantenimiento de rutina el próximo fin de semana. El servidor estará offline por aproximadamente 30 minutos. Seguinos en Discord para actualizaciones en tiempo real.',
        N'Mantenimiento',
        N'Mantenimiento de rutina. El servidor estará offline aproximadamente 30 minutos.',
        1, '2026-06-02', 'seed'
    );
GO

-- Verificación
SELECT id, title, category, is_published, published_at, created_by
FROM dbo.news ORDER BY published_at DESC;
