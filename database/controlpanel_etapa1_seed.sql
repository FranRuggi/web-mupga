-- ============================================================
-- ControlPanel — Etapa 1: seed inicial de server_info y downloads
-- Correr en SSMS contra la base mupga_admin (con usuario admin, no mupga_web_svc).
-- Re-ejecutable: no duplica si las filas ya existen.
-- ============================================================
USE mupga_admin;
GO

-- Diagnóstico: largos máximos de las columnas string (-1 = MAX).
-- Si algún INSERT tira "String or binary data would be truncated",
-- comparar acá qué columna quedó corta.
SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME IN ('server_info', 'downloads')
  AND CHARACTER_MAXIMUM_LENGTH IS NOT NULL
ORDER BY TABLE_NAME, ORDINAL_POSITION;
GO

-- ── server_info: blob JSON con todas las secciones de la página Info ──
IF NOT EXISTS (SELECT 1 FROM dbo.server_info WHERE config_key = 'secciones')
BEGIN
    INSERT INTO dbo.server_info (config_key, config_value, updated_by, updated_at)
    VALUES (
        'secciones',
        N'{
  "secciones": [
    { "id": "rates", "eyebrow": "Información", "titulo": "Información general del servidor", "tipo": "tabla", "columnas": ["Concepto", "Normal", "VIP"], "vip_col": 2, "filas": [["Versión","Season 6","Season 6"],["Experiencia (XP)","70x","100x"],["Drop rate","30%","50%"],["Zen rate","20x","30x"],["Nivel máximo","400","400"],["Master Experience","30x","50x"],["Max Stats por nivel","5","5"]] },
    { "id": "chaos", "eyebrow": "Crafteo", "titulo": "Chaos Machine", "tipo": "tabla", "columnas": ["Combinación", "Normal", "VIP"], "vip_col": 2, "filas": [["Items +10, +11","60% + Luck","65% + Luck"],["Items +12, +13","50% + Luck","55% + Luck"],["Items +14, +15","40% + Luck","45% + Luck"],["Arma Chaos","65%","75%"],["Wings Level 1","65%","75%"],["Wings Level 2","60%","70%"],["Wings Level 3","40%","50%"],["Cape of Lord Mix","60%","70%"],["Feather of Condor","50%","60%"],["Fragment of Horn Mix","40%","50%"],["Broken Horn Mix","40%","50%"],["Horn of Fenrir Mix","40%","50%"],["Ancient Hero''s Soul","40%","50%"],["Socket Weapon Mix","40%","50%"]] },
    { "id": "comandos", "eyebrow": "In-game", "titulo": "Comandos disponibles", "tipo": "tabla", "columnas": ["Comando", "Descripción"], "filas": [["/f","Agrega puntos de Fuerza."],["/a","Agrega puntos de Agilidad."],["/v","Agrega puntos de Vitalidad."],["/e","Agrega puntos de Energía."],["/c","Agrega puntos de Comando (Dark Lord)."],["/readd","Redistribuye todos los puntos de estadística."],["/reset","Resetea el personaje al nivel 1 (requiere Nv400 y 10.000.000 Zen)."],["/reset auto","Activa el reset automático: al llegar al nivel 400 el personaje se resetea solo."],["/bar","Comando disponible en el servidor."]] },
    { "id": "Party Bonus", "eyebrow": "Party Bonus", "titulo": "Experiencia en party", "tipo": "tabla", "columnas": ["Concepto", "Valor"], "filas": [["2 jugadores","EXP% + 5%"],["3 jugadores","EXP% + 5%"],["4 jugadores","EXP% + 7%"],["5 jugadores","EXP% + 10%"]] },
    { "id": "resets", "eyebrow": "Progresión", "titulo": "Sistema de resets", "tipo": "tabla", "columnas": ["Concepto", "Valor"], "filas": [["Nivel para resetear","400"],["Costo de reset (Zen)","10.000.000"],["Puntos extra por reset","350"],["Máximo de resets","300"],["Resetea stats al resetear","Sí"],["Vuelve al nivel 1","Sí"]] },
    { "id": "invasiones", "eyebrow": "Eventos", "titulo": "Invasiones", "tipo": "tabla", "columnas": ["Invasión", "Recompensa (drop x mob)", "Mobs"], "filas": [["Skeleton","4× Jewel (Soul/Bless/Life/Chaos/Creation/Key)","x4"],["Red Dragon","4× Jewel (Soul/Bless/Life/Chaos/Creation/Key)","x6"],["White Wizard","4× Jewel (Soul/Bless/Life/Chaos/Creation/Key)","x2"],["New Year","1× Jewel (Soul/Bless/Life/Chaos/Creation)","x10"],["Rabbit","1× Jewel (Soul/Bless/Life/Chaos/Creation)","x10"],["Summer","1× Jewel (Soul/Bless/Life/Chaos/Creation)","x10"],["Cursed Santa","4× Jewel (Soul/Bless/Life/Chaos/Creation/Key)","x1"]] },
    { "id": "eventos", "eyebrow": "Eventos", "titulo": "Eventos", "tipo": "tabla", "columnas": ["Evento", "Recompensa"], "filas": [["Blood Castle","4× Jewel (Soul/Bless/Life/Chaos/Creation/Key)"],["Devil Square","4× Jewel (Soul/Bless/Life/Chaos/Creation/Key)"],["Chaos Castle","4× Jewel (Soul/Bless/Life/Chaos/Creation/Key)"]] }
  ]
}',
        'seed',
        GETDATE()
    );
    PRINT 'server_info: fila "secciones" insertada.';
END
ELSE
    PRINT 'server_info: la fila "secciones" ya existe, no se toca.';
GO

-- ── downloads: registro inicial del launcher ──
IF NOT EXISTS (SELECT 1 FROM dbo.downloads WHERE item_key = 'launcher-actualizable')
BEGIN
    INSERT INTO dbo.downloads (item_key, title, description, version, size, url, is_active, sort_order, updated_by, updated_at)
    VALUES (
        'launcher-actualizable',
        N'Launcher MuPGA — Instalación completa via OneDrive',
        N'Launcher para instalar el cliente del juego. Incluye todos los archivos necesarios para jugar desde cero.',
        '1.0',
        '15 MB',
        'https://onedrive.live.com/?redeem=aHR0cHM6Ly8xZHJ2Lm1zL2YvYy8yZTczMTRiN2RhMDk2NjY0L0lnRGpfWlBpaHVJYlM3X3FYTDhTZ0N2VkFWQ0szWGppdEljTFZBdG9PMnFGQTJNP2U9bmFvdkdO&id=2E7314B7DA096664%21se293fde3e2864b1bbfea5cbf12802bd5&cid=2E7314B7DA096664',
        1,
        1,
        'seed',
        GETDATE()
    );
    PRINT 'downloads: fila "launcher-actualizable" insertada.';
END
ELSE
    PRINT 'downloads: la fila "launcher-actualizable" ya existe, no se toca.';
GO

-- Verificación rápida
SELECT config_key, LEN(config_value) AS json_len, updated_by, updated_at FROM dbo.server_info;
SELECT id, item_key, title, version, size, is_active, sort_order FROM dbo.downloads;
