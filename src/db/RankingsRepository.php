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
            "SELECT TOP {$limit} c.Name, c.Class, c.cLevel, c.ResetCount,
                    ISNULL(c.MasterResetCount,0) AS MasterResetCount, c.MapNumber,
                    mac.CountryCode
             FROM Character c
             LEFT JOIN MUPGA_ACCOUNT_COUNTRY mac ON mac.Username = c.AccountID
             WHERE {$where}
             ORDER BY c.cLevel DESC"
        )->fetchAll();
    }

    public function getByResets(int $limit = 100, array $excluded = []): array {
        $where = $this->excludeClause($excluded);
        return $this->pdo->query(
            "SELECT TOP {$limit} c.Name, c.Class, c.cLevel, c.ResetCount,
                    ISNULL(c.MasterResetCount,0) AS MasterResetCount, c.MapNumber,
                    mac.CountryCode
             FROM Character c
             LEFT JOIN MUPGA_ACCOUNT_COUNTRY mac ON mac.Username = c.AccountID
             WHERE {$where} AND c.ResetCount > 0
             ORDER BY c.ResetCount DESC, c.cLevel DESC"
        )->fetchAll();
    }

    public function getByMasterResets(int $limit = 100, array $excluded = []): array {
        $where = $this->excludeClause($excluded);
        return $this->pdo->query(
            "SELECT TOP {$limit} c.Name, c.Class, c.cLevel, c.ResetCount,
                    ISNULL(c.MasterResetCount,0) AS MasterResetCount, c.MapNumber,
                    mac.CountryCode
             FROM Character c
             LEFT JOIN MUPGA_ACCOUNT_COUNTRY mac ON mac.Username = c.AccountID
             WHERE {$where} AND ISNULL(c.MasterResetCount,0) > 0
             ORDER BY c.MasterResetCount DESC, c.ResetCount DESC, c.cLevel DESC"
        )->fetchAll();
    }

    public function getByKills(int $limit = 100, array $excluded = []): array {
        $where = $this->excludeClause($excluded);
        return $this->pdo->query(
            "SELECT TOP {$limit} c.Name, c.Class, c.cLevel,
                    ISNULL(c.PkCount,0) AS PkCount, ISNULL(c.PkLevel,3) AS PkLevel, c.MapNumber,
                    mac.CountryCode
             FROM Character c
             LEFT JOIN MUPGA_ACCOUNT_COUNTRY mac ON mac.Username = c.AccountID
             WHERE {$where} AND ISNULL(c.PkCount,0) > 0
             ORDER BY c.PkCount DESC"
        )->fetchAll();
    }

    public function getByMasterLevel(int $limit = 100, array $excluded = []): array {
        $where = $this->excludeClause($excluded);
        return $this->pdo->query(
            "SELECT TOP {$limit} c.Name, c.Class, c.cLevel,
                    ISNULL(c.mLevel,0) AS mLevel, c.ResetCount, c.MapNumber,
                    mac.CountryCode
             FROM Character c
             LEFT JOIN MUPGA_ACCOUNT_COUNTRY mac ON mac.Username = c.AccountID
             WHERE {$where} AND ISNULL(c.mLevel,0) > 0
             ORDER BY c.mLevel DESC, c.cLevel DESC"
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

    /**
     * Ranking de guilds por suma de ResetCount de sus miembros.
     * G_Score no es usado por este servidor (siempre 0), así que se
     * rankea por progresión real: total de resets acumulados del guild.
     */
    public function getGuildsByScore(int $limit = 100, array $excludeGuilds = []): array {
        $ex = $this->buildExcludeList($excludeGuilds);
        return $this->pdo->query(
            "SELECT TOP {$limit}
                 g.G_Name, g.G_Master,
                 SUM(ISNULL(c.ResetCount, 0)) AS G_Score,
                 COUNT(gm.Name)               AS G_Count,
                 CONVERT(varchar(max), g.G_Mark, 2) AS G_Mark_Hex
             FROM Guild g
             LEFT JOIN GuildMember gm ON gm.G_Name = g.G_Name
             LEFT JOIN Character   c  ON c.Name    = gm.Name
             WHERE g.G_Name NOT IN ({$ex})
             GROUP BY g.G_Name, g.G_Master, g.G_Mark
             ORDER BY G_Score DESC"
        )->fetchAll();
    }

    public function getOnlineCount(): int {
        return (int) $this->pdo->query(
            'SELECT COUNT(*) FROM MEMB_STAT WHERE ConnectStat = 1'
        )->fetchColumn();
    }
}
