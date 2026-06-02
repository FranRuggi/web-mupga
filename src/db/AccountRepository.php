<?php
/**
 * Acceso a datos de cuentas.
 * Tablas: MEMB_INFO, MEMB_STAT, CashShopData.
 */
class AccountRepository {

    public function __construct(private PDO $pdo) {}

    // -------------------------------------------------------------------------
    // Lectura
    // -------------------------------------------------------------------------

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM MEMB_INFO WHERE memb_guid = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByUsername(string $username): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM MEMB_INFO WHERE memb___id = ?'
        );
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function usernameExists(string $username): bool {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM MEMB_INFO WHERE memb___id = ?'
        );
        $stmt->execute([$username]);
        return (bool) $stmt->fetchColumn();
    }

    public function emailExists(string $email): bool {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM MEMB_INFO WHERE mail_addr = ?'
        );
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Valida usuario y contraseña.
     * Con DB_USE_MD5=true usa la función SQL fn_md5(password, username),
     * igual que el GameServer y WebEngine.
     */
    public function validateCredentials(string $username, string $password): bool {
        if (($_ENV['DB_USE_MD5'] ?? 'true') === 'true') {
            // PDO/sqlsrv no permite reusar named params → :u y :u2 distintos
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM MEMB_INFO
                 WHERE memb___id = :u
                   AND memb__pwd = [dbo].[fn_md5](:p, :u2)'
            );
            $stmt->execute([':u' => $username, ':p' => $password, ':u2' => $username]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM MEMB_INFO WHERE memb___id = ? AND memb__pwd = ?'
            );
            $stmt->execute([$username, $password]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public function isOnline(string $username): bool {
        $stmt = $this->pdo->prepare(
            'SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = ?'
        );
        $stmt->execute([$username]);
        return (bool) $stmt->fetchColumn();
    }

    public function getOnlineCount(?string $server = null): int {
        if ($server !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM MEMB_STAT WHERE ConnectStat = 1 AND ServerName = ?'
            );
            $stmt->execute([$server]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT COUNT(*) FROM MEMB_STAT WHERE ConnectStat = 1'
            );
        }
        return (int) $stmt->fetchColumn();
    }

    public function getOnlineList(?string $server = null): array {
        if ($server !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT memb___id, ServerName, IP
                 FROM MEMB_STAT WHERE ConnectStat = 1 AND ServerName = ?'
            );
            $stmt->execute([$server]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT memb___id, ServerName, IP FROM MEMB_STAT WHERE ConnectStat = 1'
            );
        }
        return $stmt->fetchAll();
    }

    public function getVIPStatus(string $username): array {
        $stmt = $this->pdo->prepare(
            'SELECT AccountLevel, AccountExpireDate FROM MEMB_INFO WHERE memb___id = ?'
        );
        $stmt->execute([$username]);
        return $stmt->fetch() ?: ['AccountLevel' => 0, 'AccountExpireDate' => null];
    }

    // -------------------------------------------------------------------------
    // Escritura
    // -------------------------------------------------------------------------

    /**
     * Registra una cuenta nueva.
     * Inserta en MEMB_INFO y crea la fila de CashShopData con saldo cero.
     */
    public function create(string $username, string $password, string $email): bool {
        // sno__numb es char(18) — rellenar hasta 18 con espacios
        $serial = str_pad('1111111111111', 18);

        $this->pdo->beginTransaction();
        try {
            if (($_ENV['DB_USE_MD5'] ?? 'true') === 'true') {
                // PDO/sqlsrv no permite reusar named params → :u, :u2, :u3 distintos
                $stmt = $this->pdo->prepare(
                    'INSERT INTO MEMB_INFO
                        (memb___id, memb__pwd, memb_name, sno__numb, mail_addr,
                         bloc_code, ctl1_code, AccountLevel, Lock,
                         AccountExpireDate, CreatedAt, WarehouseCount, ShowBanner)
                     VALUES
                        (:u, [dbo].[fn_md5](:p, :u2), :u3, :s, :e,
                         0, 0, 0, 0,
                         GETDATE(), GETDATE(), 0, 0)'
                );
                $stmt->execute([':u' => $username, ':p' => $password, ':u2' => $username, ':u3' => $username, ':s' => $serial, ':e' => $email]);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO MEMB_INFO
                        (memb___id, memb__pwd, memb_name, sno__numb, mail_addr,
                         bloc_code, ctl1_code, AccountLevel, Lock,
                         AccountExpireDate, CreatedAt, WarehouseCount, ShowBanner)
                     VALUES
                        (?, ?, ?, ?, ?,
                         0, 0, 0, 0,
                         GETDATE(), GETDATE(), 0, 0)'
                );
                $stmt->execute([$username, $password, $username, $serial, $email]);
            }

            // Fila de CashShopData necesaria para que sp_AddWCoinWithLog funcione
            $stmt = $this->pdo->prepare(
                'INSERT INTO CashShopData (AccountID, WCoinC, WCoinP, GoblinPoint) VALUES (?, 0, 0, 0)'
            );
            $stmt->execute([$username]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return true;
    }

    public function changePassword(int $id, string $username, string $newPassword): bool {
        if (($_ENV['DB_USE_MD5'] ?? 'true') === 'true') {
            $stmt = $this->pdo->prepare(
                'UPDATE MEMB_INFO
                 SET memb__pwd = [dbo].[fn_md5](:p, :u)
                 WHERE memb_guid = :id'
            );
            $stmt->execute([':p' => $newPassword, ':u' => $username, ':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE MEMB_INFO SET memb__pwd = ? WHERE memb_guid = ?'
            );
            $stmt->execute([$newPassword, $id]);
        }
        return (bool) $stmt->rowCount();
    }

    public function changeEmail(int $id, string $email): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE MEMB_INFO SET mail_addr = ? WHERE memb_guid = ?'
        );
        $stmt->execute([$email, $id]);
        return (bool) $stmt->rowCount();
    }

    public function block(int $id): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE MEMB_INFO SET bloc_code = 1 WHERE memb_guid = ?'
        );
        $stmt->execute([$id]);
        return (bool) $stmt->rowCount();
    }

    public function unblock(int $id): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE MEMB_INFO SET bloc_code = 0, bloc_expire = NULL WHERE memb_guid = ?'
        );
        $stmt->execute([$id]);
        return (bool) $stmt->rowCount();
    }

    /**
     * Activa o modifica el VIP de una cuenta vía stored procedure.
     * @param string $expireDate Formato 'YYYY-MM-DD HH:MM:SS'
     */
    public function setVIP(string $username, int $level, string $expireDate): bool {
        $stmt = $this->pdo->prepare('EXEC sp_SetAccountVIP ?, ?, ?');
        $stmt->execute([$username, $level, $expireDate]);
        return true;
    }
}
