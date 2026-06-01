<?php
/**
 * Acceso a datos de créditos / WCoin.
 * Tablas: CashShopData, CashLog.
 * Stored procedures: sp_AddWCoinWithLog, WZ_SetCoin.
 */
class CreditsRepository {

    public function __construct(private PDO $pdo) {}

    // -------------------------------------------------------------------------
    // Lectura
    // -------------------------------------------------------------------------

    /**
     * Devuelve el saldo de WCoin, WCoinP y GoblinPoint de una cuenta.
     */
    public function getBalance(string $accountId): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT WCoinC, WCoinP, GoblinPoint FROM CashShopData WHERE AccountID = ?'
        );
        $stmt->execute([$accountId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Historial de transacciones de WCoin (tabla CashLog).
     */
    public function getTransactionLog(string $accountId, int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} ID, Amount, SentDate
             FROM CashLog WHERE UserID = ? ORDER BY ID DESC"
        );
        $stmt->execute([$accountId]);
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // Escritura
    // -------------------------------------------------------------------------

    /**
     * Acredita WCoin a una cuenta usando el SP sp_AddWCoinWithLog.
     * Incluye validación de cuenta y log transaccional dentro del SP.
     *
     * El SP produce result sets internos (SELECT de diagnóstico); se consumen
     * con nextRowset() para evitar errores de PDO_SQLSRV con múltiples resultados.
     */
    public function addWCoin(string $accountId, float $amount): bool {
        $stmt = $this->pdo->prepare('EXEC sp_AddWCoinWithLog ?, ?');
        $stmt->execute([$accountId, $amount]);
        // Consumir todos los result sets que produce el SP
        do {} while ($stmt->nextRowset());
        return true;
    }

    /**
     * Ajusta el saldo de WCoinC, WCoinP y/o GoblinPoint usando WZ_SetCoin.
     * Los valores son deltas (positivos para sumar, negativos para restar).
     *
     * @param string $accountId  AccountID de la cuenta
     * @param int    $wcoinC     Delta de WCoinC (0 = sin cambio)
     * @param int    $wcoinP     Delta de WCoinP (0 = sin cambio)
     * @param int    $goblin     Delta de GoblinPoint (0 = sin cambio)
     */
    public function adjustCashShop(
        string $accountId,
        int $wcoinC = 0,
        int $wcoinP = 0,
        int $goblin = 0
    ): bool {
        // El segundo parámetro (@Name) no se usa en el cuerpo del SP
        $stmt = $this->pdo->prepare('EXEC WZ_SetCoin ?, ?, ?, ?, ?');
        $stmt->execute([$accountId, '', $wcoinC, $wcoinP, $goblin]);
        return true;
    }
}
