<?php
/**
 * Queries de rankings — solo lectura.
 * Tablas: Character, Guild, GuildMember.
 *
 * Exclusión de admins: se filtra por AccountID Y por Name.
 * Esto cubre dos casos:
 *   - La cuenta tiene AccountID = 'ruggi' → todos sus personajes desaparecen.
 *   - El personaje se llama 'ruggi' aunque la cuenta tenga otro ID → también desaparece.
 * Los valores vienen del .env (RANKINGS_EXCLUDED_ACCOUNTS), nunca de input de usuario.
 */
class RankingsRepository {

    public function __construct(private PDO $pdo) {}

    /**
     * Construye el fragmento IN ('a','b') para exclusiones hardcoded de config.
     * Solo para listas internas, nunca para input de usuario.
     */
    private function buildExcludeList(array $names): string {
        if (empty($names)) return "''";
        return implode(',', array_map(
            fn(string $n) => "'" . str_replace("'", "''", $n) . "'",
            $names
        ));
    }

    /** WHERE clause que excluye por AccountID y por Name a la vez. */
    private function excludeClause(array $excluded): string {
        if (empty($excluded)) return '1=1';
        $ex = $this->buildExcludeList($excluded);
        return "AccountID NOT IN ({$ex}) AND Name NOT IN ({$ex})";
    }

    // -------------------------------------------------------------------------
    // Rankings de personajes
    // -------------------------------------------------------------------------

    public function getByLevel(int $limit = 100, array $excluded = []): array {
        $where = $this->excludeClause($excluded);
        return $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel, ResetCount,
                    ISNULL(MasterResetCount,0) AS MasterResetCount, MapNumber
             FROM Character WHERE {$where}
             ORDER BY cLevel DESC"
        )->fetchAll();
    }

    public function getByResets(int $limit = 100, array $excluded = []): array {
        $where = $this->excludeClause($excluded);
        return $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel, ResetCount,
                    ISNULL(MasterResetCount,0) AS MasterResetCount, MapNumber
             FROM Character WHERE {$where} AND ResetCount > 0
             ORDER BY ResetCount DESC, cLevel DESC"
        )->fetchAll();
    }

    public function getByMasterResets(int $limit = 100, array $excluded = []): array {
        $where = $this->excludeClause($excluded);
        return $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel, ResetCount,
                    ISNULL(MasterResetCount,0) AS MasterResetCount, MapNumber
             FROM Character WHERE {$where} AND ISNULL(MasterResetCount,0) > 0
             ORDER BY MasterResetCount DESC, ResetCount DESC, cLevel DESC"
        )->fetchAll();
    }

    public function getByKills(int $limit = 100, array $excluded = []): array {
        $where = $this->excludeClause($excluded);
        return $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel,
                    ISNULL(PkCount,0) AS PkCount, ISNULL(PkLevel,3) AS PkLevel, MapNumber
             FROM Character WHERE {$where} AND ISNULL(PkCount,0) > 0
             ORDER BY PkCount DESC"
        )->fetchAll();
    }

    public function getByMasterLevel(int $limit = 100, array $excluded = []): array {
        $where = $this->excludeClause($excluded);
        return $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel,
                    ISNULL(mLevel,0) AS mLevel, ResetCount, MapNumber
             FROM Character WHERE {$where} AND ISNULL(mLevel,0) > 0
             ORDER BY mLevel DESC, cLevel DESC"
        )->fetchAll();
    }

    // -------------------------------------------------------------------------
    // Posición del jugador logueado
    // -------------------------------------------------------------------------

    public function getPlayerCharacterRank(string $accountId, string $type, array $excluded = []): ?array {
        $ex    = $this->buildExcludeList($excluded);
        $excl  = empty($excluded) ? '1=1' : "AccountID NOT IN ({$ex}) AND Name NOT IN ({$ex})";

        [$orderBy, $condition] = match ($type) {
            'level'        => ['cLevel DESC',                                     '1=1'],
            'kills'        => ['ISNULL(PkCount,0) DESC',                          'ISNULL(PkCount,0) > 0'],
            'masterresets' => ['ISNULL(MasterResetCount,0) DESC, ResetCount DESC, cLevel DESC',
                               'ISNULL(MasterResetCount,0) > 0'],
            'master'       => ['ISNULL(mLevel,0) DESC, cLevel DESC',              'ISNULL(mLevel,0) > 0'],
            default        => ['ResetCount DESC, cLevel DESC',                    'ResetCount > 0'],
        };

        $stmt = $this->pdo->prepare(
            "WITH ranked AS (
                SELECT Name, Class, cLevel,
                       ResetCount,
                       ISNULL(MasterResetCount,0) AS MasterResetCount,
                       ISNULL(mLevel,0) AS mLevel,
                       ISNULL(PkCount,0) AS PkCount,
                       ISNULL(PkLevel,3) AS PkLevel,
                       AccountID,
                       ROW_NUMBER() OVER (ORDER BY {$orderBy}) AS position
                FROM Character
                WHERE {$excl} AND {$condition}
             )
             SELECT TOP 1 Name, Class, cLevel, ResetCount, MasterResetCount,
                    mLevel, PkCount, PkLevel, position
             FROM ranked
             WHERE AccountID = ?
             ORDER BY position ASC"
        );
        $stmt->execute([$accountId]);
        return $stmt->fetch() ?: null;
    }

    // -------------------------------------------------------------------------
    // Guilds
    // -------------------------------------------------------------------------

    public function getGuildsByScore(int $limit = 100, array $excludeGuilds = []): array {
        $ex   = $this->buildExcludeList($excludeGuilds);
        return $this->pdo->query(
            "SELECT TOP {$limit}
                 G_Name, G_Master, G_Score, G_Count,
                 CONVERT(varchar(max), G_Mark, 2) AS G_Mark_Hex
             FROM Guild
             WHERE G_Name NOT IN ({$ex})
             ORDER BY G_Score DESC"
        )->fetchAll();
    }

    public function getOnlineCount(): int {
        return (int) $this->pdo->query(
            'SELECT COUNT(*) FROM MEMB_STAT WHERE ConnectStat = 1'
        )->fetchColumn();
    }
}
