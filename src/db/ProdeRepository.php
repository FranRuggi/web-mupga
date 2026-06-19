<?php
/**
 * Acceso a datos del módulo Prode MuPGA.
 *
 * $db   → conexión prode_user (schema prode.*)
 * $main → conexión principal (SPs de premios + MEMB_INFO para extensión de VIP)
 *
 * Regla: nunca hardcodear valores de premios — siempre leer de prode.config.
 */
class ProdeRepository {

    private PDO $db;
    private PDO $main;

    public function __construct(PDO $prode, PDO $main) {
        $this->db   = $prode;
        $this->main = $main;
    }

    // ── Config ────────────────────────────────────────────────

    /**
     * Lee toda la tabla prode.config y devuelve array clave => valor.
     */
    public function getConfig(): array {
        $stmt = $this->db->query('SELECT config_key, config_value FROM prode.config');
        $rows = $stmt->fetchAll();
        $out  = [];
        foreach ($rows as $row) {
            $out[$row['config_key']] = $row['config_value'];
        }
        return $out;
    }

    // ── Partidos ──────────────────────────────────────────────

    /**
     * Devuelve todos los partidos con la predicción del usuario si existe.
     * Resultado: ['upcoming' => [...], 'finished' => [...]]
     */
    public function getMatchesWithPredictions(string $account): array {
        $stmt = $this->db->prepare(
            "SELECT
                m.id, m.team_home, m.team_away,
                CONVERT(VARCHAR(23), m.match_datetime_utc, 126) AS match_datetime_utc,
                m.stage, m.is_locked, m.score_home, m.score_away, m.status,
                p.pred_score_home, p.pred_score_away,
                p.points_earned,   p.reward_applied
             FROM prode.matches m
             LEFT JOIN prode.predictions p
                    ON p.match_id = m.id AND p.account = ?
             ORDER BY m.match_datetime_utc ASC"
        );
        $stmt->execute([$account]);
        $rows = $stmt->fetchAll();

        $upcoming = [];
        $finished = [];

        foreach ($rows as $row) {
            $entry = [
                'id'                 => (int)$row['id'],
                'team_home'          => $row['team_home'],
                'team_away'          => $row['team_away'],
                'match_datetime_utc' => $row['match_datetime_utc'],
                'stage'              => $row['stage'],
                'is_locked'          => (bool)$row['is_locked'],
                'score_home'         => isset($row['score_home']) ? (int)$row['score_home'] : null,
                'score_away'         => isset($row['score_away']) ? (int)$row['score_away'] : null,
                'status'             => $row['status'],
                'prediction'         => null,
            ];

            if ($row['pred_score_home'] !== null) {
                $entry['prediction'] = [
                    'pred_score_home' => (int)$row['pred_score_home'],
                    'pred_score_away' => (int)$row['pred_score_away'],
                    'points_earned'   => isset($row['points_earned']) ? (int)$row['points_earned'] : null,
                ];
            }

            if ($row['status'] === 'finished') {
                $finished[] = $entry;
            } else {
                $upcoming[] = $entry;
            }
        }

        return ['upcoming' => $upcoming, 'finished' => $finished];
    }

    /**
     * Inserta o actualiza una predicción (UPSERT).
     *
     * Toda la validación corre en SQL Server para ser inmune a desfases de reloj PHP
     * y a manipulación del cliente:
     *  - partido en status 'pending'
     *  - is_locked = 0
     *  - GETDATE() + 60 min < match_datetime_utc  (cutoff server-side + auto-lock al arrancar)
     *
     * UPDLOCK + HOLDLOCK en el SELECT y una única transacción eliminan la race condition
     * TOCTOU entre la validación y el MERGE.
     *
     * @throws RuntimeException si la validación falla.
     */
    public function savePrediction(
        string $account,
        int    $matchId,
        int    $homeScore,
        int    $awayScore
    ): bool {
        $this->db->beginTransaction();
        try {
            // Una sola query server-side: existencia + estado + lock + cutoff temporal.
            // UPDLOCK/HOLDLOCK garantizan que ningún resolveMatch() intercale entre
            // esta lectura y el MERGE subsiguiente.
            $stmt = $this->db->prepare(
                "SELECT id FROM prode.matches WITH (UPDLOCK, HOLDLOCK)
                 WHERE id = ?
                   AND status = 'pending'
                   AND is_locked = 0
                   AND DATEADD(MINUTE, 60, GETDATE()) < match_datetime_utc"
            );
            $stmt->execute([$matchId]);

            if (!$stmt->fetch()) {
                throw new RuntimeException('Partido no encontrado o predicciones cerradas.');
            }

            // MERGE: INSERT si no existe, UPDATE si ya existe.
            // submitted_at = GETDATE() en ambos paths: nunca depende del DEFAULT del schema.
            $stmt = $this->db->prepare(
                'MERGE prode.predictions AS t
                 USING (SELECT ? AS account, ? AS match_id) AS s
                    ON t.account = s.account AND t.match_id = s.match_id
                 WHEN MATCHED THEN
                     UPDATE SET
                         pred_score_home = ?,
                         pred_score_away = ?,
                         submitted_at    = GETDATE()
                 WHEN NOT MATCHED THEN
                     INSERT (account, match_id, pred_score_home, pred_score_away, submitted_at)
                     VALUES (?, ?, ?, ?, GETDATE());'
            );
            $stmt->execute([
                $account, $matchId,
                $homeScore, $awayScore,
                $account, $matchId, $homeScore, $awayScore,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return true;
    }

    // ── Ranking ───────────────────────────────────────────────

    /**
     * Devuelve el top 50 ordenado por puntos totales.
     */
    public function getRanking(): array {
        $stmt = $this->db->prepare(
            'SELECT TOP 50
                pa.account,
                ISNULL(s.total_points, 0) AS total_points,
                ISNULL(s.exact_hits,   0) AS exact_hits,
                ISNULL(s.winner_hits,  0) AS winner_hits,
                pa.total_predictions
             FROM (
                 SELECT account, COUNT(*) AS total_predictions
                 FROM prode.predictions
                 GROUP BY account
             ) AS pa
             LEFT JOIN prode.scores s ON s.account = pa.account
             ORDER BY total_points DESC, exact_hits DESC, winner_hits DESC, pa.total_predictions DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ── Admin ─────────────────────────────────────────────────

    /**
     * Inserta un nuevo partido.
     */
    public function createMatch(
        string $teamHome,
        string $teamAway,
        string $datetimeUtc,
        string $stage
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$teamHome, $teamAway, $datetimeUtc, $stage]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Resuelve un partido: calcula puntos, actualiza scores y aplica premios.
     * Si el partido ya está 'finished', lanza excepción (idempotencia).
     *
     * Estructura:
     *  1. Transacción en prode.* (puntos + scores)
     *  2. Fuera de transacción: SPs de premios en $this->main
     *     (reward_applied = 0 garantiza idempotencia si se llama dos veces)
     *
     * @throws RuntimeException si el partido no existe o ya fue resuelto.
     */
    public function resolveMatch(int $matchId, int $scoreHome, int $scoreAway): void {
        // Verificar que el partido existe y no fue resuelto aún
        $stmt = $this->db->prepare('SELECT status FROM prode.matches WHERE id = ?');
        $stmt->execute([$matchId]);
        $match = $stmt->fetch();

        if (!$match) {
            throw new RuntimeException('Partido no encontrado.');
        }
        if ($match['status'] === 'finished') {
            throw new RuntimeException('El partido ya fue resuelto.');
        }

        $realWinner = $this->winner($scoreHome, $scoreAway);
        $config     = $this->getConfig();

        // ── Fase 1: transacción en prode.* ────────────────────
        $this->db->beginTransaction();
        try {
            // Actualizar el partido
            $stmt = $this->db->prepare(
                "UPDATE prode.matches
                 SET score_home = ?, score_away = ?, status = 'finished', is_locked = 1
                 WHERE id = ?"
            );
            $stmt->execute([$scoreHome, $scoreAway, $matchId]);

            // Obtener todas las predicciones para este partido
            $stmt = $this->db->prepare(
                'SELECT id, account, pred_score_home, pred_score_away, reward_applied
                 FROM prode.predictions WHERE match_id = ?'
            );
            $stmt->execute([$matchId]);
            $predictions = $stmt->fetchAll();

            foreach ($predictions as $pred) {
                $predWinner = $this->winner(
                    (int)$pred['pred_score_home'],
                    (int)$pred['pred_score_away']
                );

                $isExact  = (int)$pred['pred_score_home'] === $scoreHome
                         && (int)$pred['pred_score_away'] === $scoreAway;
                $isWinner = !$isExact && $predWinner === $realWinner;

                $points      = $isExact ? 3 : ($isWinner ? 1 : 0);
                $exactIncr   = $isExact  ? 1 : 0;
                $winnerIncr  = $isWinner ? 1 : 0;

                // Guardar puntos en la predicción
                $stmt2 = $this->db->prepare(
                    'UPDATE prode.predictions SET points_earned = ? WHERE id = ?'
                );
                $stmt2->execute([$points, $pred['id']]);

                // UPSERT en prode.scores si ganó puntos
                if ($points > 0) {
                    $stmt2 = $this->db->prepare(
                        'MERGE prode.scores AS t
                         USING (SELECT ? AS account) AS s ON t.account = s.account
                         WHEN MATCHED THEN
                             UPDATE SET
                                 total_points = t.total_points + ?,
                                 exact_hits   = t.exact_hits   + ?,
                                 winner_hits  = t.winner_hits  + ?,
                                 last_updated = GETDATE()
                         WHEN NOT MATCHED THEN
                             INSERT (account, total_points, exact_hits, winner_hits, last_updated)
                             VALUES (?, ?, ?, ?, GETDATE());'
                    );
                    $stmt2->execute([
                        $pred['account'], $points, $exactIncr, $winnerIncr,
                        $pred['account'], $points, $exactIncr, $winnerIncr,
                    ]);
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        // ── Fase 2: premios vía SPs (fuera de transacción) ────
        // Re-leer predicciones con reward_applied = 0 para procesar premios
        $stmt = $this->db->prepare(
            'SELECT id, account, pred_score_home, pred_score_away, points_earned, reward_applied
             FROM prode.predictions WHERE match_id = ? AND reward_applied = 0 AND points_earned > 0'
        );
        $stmt->execute([$matchId]);
        $pending = $stmt->fetchAll();

        foreach ($pending as $pred) {
            $account = $pred['account'];
            $isExact = (int)$pred['pred_score_home'] === $scoreHome
                    && (int)$pred['pred_score_away'] === $scoreAway;

            $wcoinAmount = (int)($isExact
                ? ($config['reward_exact_wcoins']   ?? 0)
                : ($config['reward_winner_wcoins']  ?? 0));

            $vipDays = (int)($isExact
                ? ($config['reward_exact_vip_days']  ?? 0)
                : ($config['reward_winner_vip_days'] ?? 0));

            // Premio WCoin — mismo patrón que CreditsRepository::addWCoin()
            if ($wcoinAmount > 0) {
                $sp = $this->main->prepare('EXEC sp_AddWCoinWithLog ?, ?');
                $sp->execute([$account, $wcoinAmount]);
                do {} while ($sp->nextRowset());
            }

            // Premio VIP — mismo patrón que AccountRepository::setVIP()
            if ($vipDays > 0) {
                $sp = $this->main->prepare('EXEC sp_SetAccountGOLDVIP ?, ?');
                $sp->execute([$account, $vipDays]);
            }

            // Marcar como aplicado
            $upd = $this->db->prepare(
                'UPDATE prode.predictions SET reward_applied = 1 WHERE id = ?'
            );
            $upd->execute([$pred['id']]);
        }
    }

    // ── Helpers privados ──────────────────────────────────────

    /**
     * Devuelve 'H' (home), 'A' (away) o 'D' (draw).
     */
    private function winner(int $home, int $away): string {
        if ($home > $away) return 'H';
        if ($away > $home) return 'A';
        return 'D';
    }

}
