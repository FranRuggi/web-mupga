-- ============================================================
-- Prode MuPGA - INSERT de partidos FIFA World Cup 2026
-- Solo partidos desde el domingo 14/06 en adelante
-- Todos los horarios en UTC
-- Ejecutar DESPUÉS de prode_setup.sql
-- ============================================================

-- ============================================================
-- FASE DE GRUPOS (14 jun - 27 jun)
-- ============================================================

-- DOMINGO 14 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Alemania',    'Curazao',      '2026-06-14 17:00:00', 'Grupo E');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Países Bajos','Japón',        '2026-06-14 20:00:00', 'Grupo F');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Costa de Marfil','Ecuador',   '2026-06-14 23:00:00', 'Grupo E');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Suecia',      'Túnez',        '2026-06-15 02:00:00', 'Grupo F');

-- LUNES 15 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('España',      'Cabo Verde',   '2026-06-15 16:00:00', 'Grupo H');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Bélgica',     'Egipto',       '2026-06-15 19:00:00', 'Grupo G');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Arabia Saudita','Uruguay',    '2026-06-15 22:00:00', 'Grupo H');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Irán',        'Nueva Zelanda','2026-06-16 01:00:00', 'Grupo G');

-- MARTES 16 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Francia',     'Senegal',      '2026-06-16 19:00:00', 'Grupo I');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Irak',        'Noruega',      '2026-06-16 22:00:00', 'Grupo I');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Argentina',   'Argelia',      '2026-06-17 01:00:00', 'Grupo J');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Austria',     'Jordania',     '2026-06-17 04:00:00', 'Grupo J');

-- MIERCOLES 17 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Portugal',    'RD Congo',     '2026-06-17 17:00:00', 'Grupo K');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Inglaterra',  'Croacia',      '2026-06-17 20:00:00', 'Grupo L');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Ghana',       'Panamá',       '2026-06-17 23:00:00', 'Grupo L');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Uzbekistán',  'Colombia',     '2026-06-18 02:00:00', 'Grupo K');

-- JUEVES 18 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Chequia',     'Sudáfrica',    '2026-06-18 16:00:00', 'Grupo A');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Suiza',       'Bosnia y Herz.','2026-06-18 19:00:00', 'Grupo B');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Canadá',      'Qatar',        '2026-06-18 22:00:00', 'Grupo B');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('México',      'Corea del Sur','2026-06-19 01:00:00', 'Grupo A');

-- VIERNES 19 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('EE.UU.',      'Australia',    '2026-06-19 19:00:00', 'Grupo D');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Escocia',     'Marruecos',    '2026-06-19 22:00:00', 'Grupo C');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Brasil',      'Haití',        '2026-06-20 00:30:00', 'Grupo C');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Turquía',     'Paraguay',     '2026-06-20 03:00:00', 'Grupo D');

-- SABADO 20 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Países Bajos','Suecia',       '2026-06-20 17:00:00', 'Grupo F');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Alemania',    'Costa de Marfil','2026-06-20 20:00:00', 'Grupo E');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Ecuador',     'Curazao',      '2026-06-21 00:00:00', 'Grupo E');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Túnez',       'Japón',        '2026-06-21 02:00:00', 'Grupo F');  -- esperar que termine 20/06

-- DOMINGO 21 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('España',      'Arabia Saudita','2026-06-21 16:00:00', 'Grupo H');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Bélgica',     'Irán',         '2026-06-21 19:00:00', 'Grupo G');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Uruguay',     'Cabo Verde',   '2026-06-21 22:00:00', 'Grupo H');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Nueva Zelanda','Egipto',      '2026-06-22 01:00:00', 'Grupo G');

-- LUNES 22 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Argentina',   'Austria',      '2026-06-22 17:00:00', 'Grupo J');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Francia',     'Irak',         '2026-06-22 21:00:00', 'Grupo I');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Noruega',     'Senegal',      '2026-06-23 00:00:00', 'Grupo I');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Jordania',    'Argelia',      '2026-06-23 03:00:00', 'Grupo J');

-- MARTES 23 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Portugal',    'Uzbekistán',   '2026-06-23 17:00:00', 'Grupo K');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Inglaterra',  'Ghana',        '2026-06-23 20:00:00', 'Grupo L');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Panamá',      'Croacia',      '2026-06-23 23:00:00', 'Grupo L');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Colombia',    'RD Congo',     '2026-06-24 02:00:00', 'Grupo K');

-- MIERCOLES 24 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Suiza',       'Canadá',       '2026-06-24 19:00:00', 'Grupo B');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Bosnia y Herz.','Qatar',      '2026-06-24 19:00:00', 'Grupo B');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Escocia',     'Brasil',       '2026-06-24 22:00:00', 'Grupo C');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Marruecos',   'Haití',        '2026-06-24 22:00:00', 'Grupo C');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Chequia',     'México',       '2026-06-25 01:00:00', 'Grupo A');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Sudáfrica',   'Corea del Sur','2026-06-25 01:00:00', 'Grupo A');

-- JUEVES 25 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Ecuador',     'Alemania',     '2026-06-25 20:00:00', 'Grupo E');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Curazao',     'Costa de Marfil','2026-06-25 20:00:00', 'Grupo E');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Japón',       'Suecia',       '2026-06-25 23:00:00', 'Grupo F');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Túnez',       'Países Bajos', '2026-06-25 23:00:00', 'Grupo F');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Turquía',     'EE.UU.',       '2026-06-26 02:00:00', 'Grupo D');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Paraguay',    'Australia',    '2026-06-26 02:00:00', 'Grupo D');

-- VIERNES 26 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Noruega',     'Francia',      '2026-06-26 19:00:00', 'Grupo I');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Senegal',     'Irak',         '2026-06-26 19:00:00', 'Grupo I');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Cabo Verde',  'Arabia Saudita','2026-06-27 00:00:00', 'Grupo H');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Uruguay',     'España',       '2026-06-27 00:00:00', 'Grupo H');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Egipto',      'Irán',         '2026-06-27 03:00:00', 'Grupo G');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Nueva Zelanda','Bélgica',     '2026-06-27 03:00:00', 'Grupo G');

-- SABADO 27 JUNIO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Panamá',      'Inglaterra',   '2026-06-27 21:00:00', 'Grupo L');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Croacia',     'Ghana',        '2026-06-27 21:00:00', 'Grupo L');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Colombia',    'Portugal',     '2026-06-27 23:30:00', 'Grupo K');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('RD Congo',    'Uzbekistán',   '2026-06-27 23:30:00', 'Grupo K');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Argelia',     'Austria',      '2026-06-28 02:00:00', 'Grupo J');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Jordania',    'Argentina',    '2026-06-28 02:00:00', 'Grupo J');

-- ============================================================
-- FASE ELIMINATORIA (equipos se definen durante el torneo)
-- Insertar estos con team_home/team_away como placeholder.
-- Actualizar con admin_match.php cuando se conozcan los equipos.
-- ============================================================

-- RONDA DE 32 (28 jun - 3 jul)
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-06-28 19:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-06-29 17:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-06-29 20:30:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-06-30 01:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-06-30 17:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-06-30 21:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-01 01:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-01 16:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-01 20:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-01 20:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-02 19:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-02 23:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-03 03:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-03 18:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-03 22:00:00', 'Ronda de 32');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-04 01:30:00', 'Ronda de 32');

-- RONDA DE 16 (4 jul - 7 jul)
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-04 17:00:00', 'Ronda de 16');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-04 21:00:00', 'Ronda de 16');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-05 20:00:00', 'Ronda de 16');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-06 00:00:00', 'Ronda de 16');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-06 19:00:00', 'Ronda de 16');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-07 00:00:00', 'Ronda de 16');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-07 16:00:00', 'Ronda de 16');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-07 20:00:00', 'Ronda de 16');

-- CUARTOS DE FINAL (9 jul - 11 jul)
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-09 20:00:00', 'Cuartos de Final');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-10 19:00:00', 'Cuartos de Final');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-11 20:00:00', 'Cuartos de Final');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-12 01:00:00', 'Cuartos de Final');

-- SEMIFINALES (14 jul - 15 jul)
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-14 19:00:00', 'Semifinal');
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-15 19:00:00', 'Semifinal');

-- TERCER PUESTO
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-18 21:00:00', 'Tercer Puesto');

-- FINAL
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage) VALUES ('Por definir', 'Por definir', '2026-07-19 19:00:00', 'Final');

-- ============================================================
-- VERIFICACION
-- ============================================================
SELECT COUNT(*) AS total_partidos FROM prode.matches;
SELECT stage, COUNT(*) AS cantidad FROM prode.matches GROUP BY stage ORDER BY MIN(match_datetime_utc);