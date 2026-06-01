<?php
/**
 * Queries de rankings — solo lectura.
 * Tablas: Character, Guild, GuildMember.
 *
 * Nota sobre Gens: igcn.tables.php define _TBL_GENS_ = 'IGC_Gens', pero ese nombre
 * no aparece en el dump script.sql (sí aparecen Gens_Duprian, Gens_Varnert, Gens_Rank).
 * El ranking de Gens queda pendiente de verificar el nombre real de la tabla.
 */
class RankingsRepository {

    public function __construct(private PDO $pdo) {}

    /**
     * Construye el fragmento IN ('a','b') para excluir nombres.
     * Solo para listas de config interna, nunca para input de usuario.
     */
    private function buildExcludeList(array $names): string {
        if (empty($names)) {
            return "''";
        }
        $escaped = array_map(
            fn(string $n) => "'" . str_replace("'", "''", $n) . "'",
            $names
        );
        return implode(',', $escaped);
    }

    // -------------------------------------------------------------------------
    // Rankings de personajes
    // -------------------------------------------------------------------------

    public function getByLevel(int $limit = 25, array $exclude = []): array {
        $ex   = $this->buildExcludeList($exclude);
        $stmt = $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel, ResetCount, MasterResetCount, MapNumber
             FROM Character
             WHERE Name NOT IN ({$ex})
             ORDER BY cLevel DESC"
        );
        return $stmt->fetchAll();
    }

    public function getByResets(int $limit = 25, array $exclude = []): array {
        $ex   = $this->buildExcludeList($exclude);
        $stmt = $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel, ResetCount, MasterResetCount, MapNumber
             FROM Character
             WHERE Name NOT IN ({$ex}) AND ResetCount > 0
             ORDER BY ResetCount DESC, cLevel DESC"
        );
        return $stmt->fetchAll();
    }

    public function getByMasterResets(int $limit = 25, array $exclude = []): array {
        $ex   = $this->buildExcludeList($exclude);
        $stmt = $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel, ResetCount, MasterResetCount, MapNumber
             FROM Character
             WHERE Name NOT IN ({$ex}) AND MasterResetCount > 0
             ORDER BY MasterResetCount DESC, ResetCount DESC, cLevel DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Ranking de killers PK.
     * PkCount = cantidad de kills; PkLevel: 0=Hero, 3=Normal, 4+=Murder.
     */
    public function getByKills(int $limit = 25, array $exclude = []): array {
        $ex   = $this->buildExcludeList($exclude);
        $stmt = $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel, PkCount, PkLevel, MapNumber
             FROM Character
             WHERE Name NOT IN ({$ex}) AND PkCount > 0
             ORDER BY PkCount DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Ranking de nivel Master.
     * Columna mLevel (en IGCN S6 está en la tabla Character — verificar en DDL completo).
     */
    public function getByMasterLevel(int $limit = 25, array $exclude = []): array {
        $ex   = $this->buildExcludeList($exclude);
        $stmt = $this->pdo->query(
            "SELECT TOP {$limit} Name, Class, cLevel, mLevel, ResetCount, MapNumber
             FROM Character
             WHERE Name NOT IN ({$ex}) AND mLevel > 0
             ORDER BY mLevel DESC, cLevel DESC"
        );
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // Ranking de guilds
    // -------------------------------------------------------------------------

    /**
     * Ranking de guilds por G_Score (campo nativo del servidor).
     */
    public function getGuildsByScore(int $limit = 25, array $excludeGuilds = []): array {
        $ex   = $this->buildExcludeList($excludeGuilds);
        $stmt = $this->pdo->query(
            "SELECT TOP {$limit}
                 G_Name, G_Master, G_Score, G_Count,
                 CONVERT(varchar(max), G_Mark, 2) AS G_Mark_Hex
             FROM Guild
             WHERE G_Name NOT IN ({$ex})
             ORDER BY G_Score DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Ranking de guilds por suma de stats de sus miembros.
     * Fórmula: STR + AGI + VIT + ENE + CMD de todos los personajes del guild.
     */
    public function getGuildsByMemberStats(int $limit = 25, array $excludeGuilds = []): array {
        $ex   = $this->buildExcludeList($excludeGuilds);
        $stmt = $this->pdo->query(
            "SELECT TOP {$limit}
                 gm.G_Name,
                 g.G_Master,
                 SUM(c.Strength + c.Dexterity + c.Vitality + c.Energy + c.Leadership) AS TotalStats,
                 CONVERT(varchar(max), g.G_Mark, 2) AS G_Mark_Hex
             FROM GuildMember gm
             INNER JOIN Character c ON c.Name = gm.Name
             INNER JOIN Guild g ON g.G_Name = gm.G_Name
             WHERE gm.G_Name NOT IN ({$ex})
             GROUP BY gm.G_Name, g.G_Master, g.G_Mark
             ORDER BY TotalStats DESC"
        );
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // Online count (útil para la home)
    // -------------------------------------------------------------------------

    public function getOnlineCount(): int {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) FROM MEMB_STAT WHERE ConnectStat = 1'
        );
        return (int) $stmt->fetchColumn();
    }
}
