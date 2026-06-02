<?php
/**
 * Acceso a datos de personajes.
 * Tablas: Character, AccountCharacter.
 *
 * Todas las escrituras son seguras con el jugador online en MuPGA (MuEmu Louis v31).
 * Ver .claude/docs/capability-matrix.md para el razonamiento.
 */
class CharacterRepository {

    public function __construct(private PDO $pdo) {}

    // -------------------------------------------------------------------------
    // Lectura
    // -------------------------------------------------------------------------

    public function getByName(string $name): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM Character WHERE Name = ?');
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Devuelve todos los personajes de una cuenta con los campos más usados.
     */
    public function getByAccount(string $username): array {
        $stmt = $this->pdo->prepare(
            'SELECT Name, Class, cLevel, ResetCount,
                    ISNULL(MasterResetCount,0) AS MasterResetCount,
                    MapNumber, ISNULL(LevelUpPoint,0) AS LevelUpPoint
             FROM Character WHERE AccountID = ?'
        );
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve el nombre del personaje principal (GameIDC) de la cuenta.
     */
    public function getMainCharacterName(string $username): ?string {
        $stmt = $this->pdo->prepare(
            'SELECT GameIDC FROM AccountCharacter WHERE Id = ?'
        );
        $stmt->execute([$username]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }

    public function exists(string $name): bool {
        $stmt = $this->pdo->prepare('SELECT 1 FROM Character WHERE Name = ?');
        $stmt->execute([$name]);
        return (bool) $stmt->fetchColumn();
    }

    public function belongsToAccount(string $name, string $username): bool {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM Character WHERE Name = ? AND AccountID = ?'
        );
        $stmt->execute([$name, $username]);
        return (bool) $stmt->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // Escritura
    // -------------------------------------------------------------------------

    /**
     * Deduce zen (Money) del personaje. No ejecuta si el saldo es insuficiente.
     */
    public function deductZen(string $name, int $amount): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE Character SET Money = Money - ?
             WHERE Name = ? AND Money >= ?'
        );
        $stmt->execute([$amount, $name, $amount]);
        return (bool) $stmt->rowCount();
    }

    /**
     * Mueve el personaje a un mapa/coordenadas (unstick).
     * Por defecto: Lorencia (mapa 0, coord 125,125).
     */
    public function unstick(string $name, int $map = 0, int $x = 125, int $y = 125): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE Character SET MapNumber = ?, MapPosX = ?, MapPosY = ? WHERE Name = ?'
        );
        $stmt->execute([$map, $x, $y, $name]);
        return (bool) $stmt->rowCount();
    }

    /**
     * Limpia el estado PK del personaje.
     * @param int $pkLevel Nivel PK resultante: 3 = Commoner (normal).
     */
    public function clearPK(string $name, int $pkLevel = 3, int $zenCost = 0): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE Character
             SET PkLevel = ?, PkTime = 0, Money = Money - ?
             WHERE Name = ?'
        );
        $stmt->execute([$pkLevel, $zenCost, $name]);
        return (bool) $stmt->rowCount();
    }

    /**
     * Resetea las estadísticas del personaje a sus valores base.
     * @param array $baseStats ['str'=>int, 'agi'=>int, 'vit'=>int, 'ene'=>int, 'cmd'=>int]
     * @param int   $newPoints Puntos de level-up a devolver al jugador
     */
    public function resetStats(string $name, array $baseStats, int $newPoints, int $zenCost = 0): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE Character
             SET Strength = ?, Dexterity = ?, Vitality = ?, Energy = ?, Leadership = ?,
                 LevelUpPoint = ?, Money = Money - ?
             WHERE Name = ?'
        );
        $stmt->execute([
            $baseStats['str'],
            $baseStats['agi'],
            $baseStats['vit'],
            $baseStats['ene'],
            $baseStats['cmd'] ?? 0,
            $newPoints,
            $zenCost,
            $name,
        ]);
        return (bool) $stmt->rowCount();
    }

    /**
     * Agrega puntos de estadísticas al personaje y descuenta los LevelUpPoints usados.
     * @param array $newStats ['str'=>int, 'agi'=>int, 'vit'=>int, 'ene'=>int, 'cmd'=>int]
     */
    public function addStats(string $name, array $newStats, int $pointsUsed, int $zenCost = 0): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE Character
             SET Strength = ?, Dexterity = ?, Vitality = ?, Energy = ?, Leadership = ?,
                 LevelUpPoint = LevelUpPoint - ?, Money = Money - ?
             WHERE Name = ?'
        );
        $stmt->execute([
            $newStats['str'],
            $newStats['agi'],
            $newStats['vit'],
            $newStats['ene'],
            $newStats['cmd'] ?? 0,
            $pointsUsed,
            $zenCost,
            $name,
        ]);
        return (bool) $stmt->rowCount();
    }

    /**
     * Ejecuta un reset de personaje.
     * Acepta un array de opciones para controlar qué campos se actualizan.
     *
     * Claves válidas de $data:
     *   level      (int)   — nivel resultante, default 1
     *   class      (int)   — clase resultante (para revertir evolución)
     *   str/agi/vit/ene/cmd (int) — stats base tras el reset
     *   points     (int)   — LevelUpPoints nuevos
     *   resets     (int)   — nuevo valor de ResetCount
     *   zen_cost   (int)   — zen a descontar
     *   clear_inventory (bool) — nullear el inventario
     *   clear_quest     (bool) — nullear el estado de quests
     */
    public function reset(string $name, array $data): bool {
        $sets   = [];
        $params = [':name' => $name];

        $colMap = [
            'level'  => 'cLevel',
            'class'  => 'Class',
            'str'    => 'Strength',
            'agi'    => 'Dexterity',
            'vit'    => 'Vitality',
            'ene'    => 'Energy',
            'cmd'    => 'Leadership',
            'points' => 'LevelUpPoint',
            'resets' => 'ResetCount',
        ];

        foreach ($colMap as $key => $col) {
            if (isset($data[$key])) {
                $sets[]          = "{$col} = :{$key}";
                $params[":{$key}"] = (int) $data[$key];
            }
        }

        if (!empty($data['zen_cost'])) {
            $sets[]        = 'Money = Money - :zen';
            $params[':zen'] = (int) $data['zen_cost'];
        }
        if (!empty($data['clear_inventory'])) {
            $sets[] = 'Inventory = NULL';
        }
        if (!empty($data['clear_quest'])) {
            $sets[] = 'Quest = NULL';
        }

        if (empty($sets)) {
            return false;
        }

        $sql  = 'UPDATE Character SET ' . implode(', ', $sets) . ' WHERE Name = :name';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->rowCount();
    }

    /**
     * Limpia la lista de habilidades (para reset de árbol de maestría).
     * MagicList es varbinary — nullear es seguro; nunca escribir bytes arbitrarios.
     */
    public function clearMagicList(string $name): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE Character SET MagicList = NULL WHERE Name = ?'
        );
        $stmt->execute([$name]);
        return (bool) $stmt->rowCount();
    }

    /**
     * Actualiza los puntos del árbol de habilidades Master.
     * Columna: mlPoint (a verificar en DDL completo de la instancia).
     */
    public function updateMasterPoints(string $name, int $points): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE Character SET mlPoint = ? WHERE Name = ?'
        );
        $stmt->execute([$points, $name]);
        return (bool) $stmt->rowCount();
    }
}
