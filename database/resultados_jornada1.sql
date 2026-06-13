-- ============================================================
-- resultados_jornada1.sql
-- Carga los resultados reales de los partidos ya jugados
-- (11-12 de junio de 2026) en prode.matches.
--
-- IMPORTANTE: este script NO ejecuta resolveMatch ni otorga premios.
-- Solo actualiza el estado visible en la UI. Úsalo cuando el prode
-- arrancó después de que estos partidos ya se habían jugado.
--
-- Ejecutar en SSMS conectado a la base de datos del VPS.
-- ============================================================

-- ── Verificación previa: ver los partidos que vamos a actualizar ──────────
SELECT id, stage, team_home, team_away,
       score_home, score_away, status, is_locked,
       CONVERT(VARCHAR(19), match_datetime_utc, 126) AS fecha_utc
FROM prode.matches
WHERE team_home IN ('México', 'Corea del Sur', 'Canadá', 'EE.UU.')
   OR team_away IN ('Sudáfrica', 'Chequia', 'Bosnia y Herz.', 'Paraguay')
ORDER BY match_datetime_utc;

-- ── Jornada 1 — 11 de junio ──────────────────────────────────────────────

-- Grupo A: México 2-0 Sudáfrica
UPDATE prode.matches
SET score_home = 2, score_away = 0, status = 'finished', is_locked = 1
WHERE team_home = 'México' AND team_away = 'Sudáfrica';

-- Grupo A: Corea del Sur 3-3 Chequia
UPDATE prode.matches
SET score_home = 3, score_away = 3, status = 'finished', is_locked = 1
WHERE team_home = 'Corea del Sur' AND team_away = 'Chequia';

-- ── Jornada 1 — 12 de junio ──────────────────────────────────────────────

-- Grupo B: Canadá 0-0 Bosnia y Herz.
UPDATE prode.matches
SET score_home = 0, score_away = 0, status = 'finished', is_locked = 1
WHERE team_home = 'Canadá' AND team_away = 'Bosnia y Herz.';

-- ── EE.UU. vs Paraguay — EN CURSO al generar este script ─────────────────
-- prode.matches no soporta status='live'; el partido queda como 'pending'
-- hasta que finalice. Cuando termine, ejecutar este bloque con los goles reales:
/*
UPDATE prode.matches
SET score_home = X, score_away = Y, status = 'finished', is_locked = 1
WHERE team_home = 'EE.UU.' AND team_away = 'Paraguay';
*/

-- ── Verificación post-update ──────────────────────────────────────────────
SELECT id, stage, team_home, team_away,
       score_home, score_away, status, is_locked
FROM prode.matches
WHERE team_home IN ('México', 'Corea del Sur', 'Canadá', 'EE.UU.')
   OR team_away IN ('Sudáfrica', 'Chequia', 'Bosnia y Herz.', 'Paraguay')
ORDER BY match_datetime_utc;
