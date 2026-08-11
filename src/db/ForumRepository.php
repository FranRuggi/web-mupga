<?php
/**
 * Acceso a datos del foro (schema forum en mupga_admin, vía ForumDatabase).
 * Nunca toca datos de juego — el nombre de personaje se resuelve aparte
 * (CharacterRepository, conexión principal) y se guarda denormalizado acá
 * (author_display_name) al momento de postear, mismo criterio que
 * reclamos.reclamos.nick.
 */
class ForumRepository {

    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // -------------------------------------------------------------------------
    // Categorías
    // -------------------------------------------------------------------------

    /**
     * SQL Server devuelve BIT vía sqlsrv como string ("0"/"1"), no bool — y "0" es
     * un string TRUTHY en JS. Sin este cast, cualquier checkbox lee como tildado
     * siempre en el frontend (bug real: admin_only_post/is_pinned/is_locked
     * quedaban "prendidos" para todas las categorías/hilos sin importar el valor
     * real en la base). Cast explícito acá, una sola vez, para que todo lo que
     * sale de este repositorio hacia el JSON tenga tipos reales.
     */
    private function mapCategoryRow(array $row): array {
        $row['id']              = (int) $row['id'];
        $row['sort_order']      = (int) $row['sort_order'];
        $row['admin_only_post'] = (bool) (int) $row['admin_only_post'];
        return $row;
    }

    private function mapThreadRow(array $row): array {
        $row['id']          = (int) $row['id'];
        $row['category_id'] = (int) $row['category_id'];
        $row['is_pinned']   = (bool) (int) $row['is_pinned'];
        $row['is_locked']   = (bool) (int) $row['is_locked'];
        if (isset($row['reply_count'])) $row['reply_count'] = (int) $row['reply_count'];
        return $row;
    }

    private function mapPostRow(array $row): array {
        $row['id']        = (int) $row['id'];
        $row['thread_id'] = (int) $row['thread_id'];
        return $row;
    }

    public function getCategories(): array {
        $stmt = $this->pdo->query(
            'SELECT id, name, slug, description, sort_order, admin_only_post
             FROM forum.categories
             ORDER BY sort_order ASC, name ASC'
        );
        return array_map([$this, 'mapCategoryRow'], $stmt->fetchAll());
    }

    public function getCategory(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM forum.categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->mapCategoryRow($row) : null;
    }

    public function createCategory(string $name, string $slug, ?string $description, int $sortOrder, bool $adminOnlyPost): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $slug, $description, $sortOrder, $adminOnlyPost ? 1 : 0]);
        return (int) $stmt->fetchColumn();
    }

    public function updateCategory(int $id, string $name, ?string $description, int $sortOrder, bool $adminOnlyPost): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.categories
             SET name = ?, description = ?, sort_order = ?, admin_only_post = ?
             WHERE id = ?'
        );
        $stmt->execute([$name, $description, $sortOrder, $adminOnlyPost ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    public function deleteCategory(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM forum.categories WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->rowCount();
    }

    public function categoryHasThreads(int $id): bool {
        $stmt = $this->pdo->prepare('SELECT TOP 1 1 FROM forum.threads WHERE category_id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // Hilos
    // -------------------------------------------------------------------------

    public function getThreadsByCategory(int $categoryId, int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} id, category_id, title, author_account, author_display_name,
                    is_pinned, is_locked, reply_count, created_at, last_post_at
             FROM forum.threads
             WHERE category_id = ?
             ORDER BY is_pinned DESC, last_post_at DESC"
        );
        $stmt->execute([$categoryId]);
        return array_map([$this, 'mapThreadRow'], $stmt->fetchAll());
    }

    public function getThread(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM forum.threads WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->mapThreadRow($row) : null;
    }

    public function createThread(int $categoryId, string $title, string $body, string $account, string $displayName): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.threads (category_id, title, body, author_account, author_display_name)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$categoryId, $title, $body, $account, $displayName]);
        return (int) $stmt->fetchColumn();
    }

    public function editThread(int $id, string $title, string $body): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads SET title = ?, body = ?, edited_at = SYSUTCDATETIME() WHERE id = ?'
        );
        $stmt->execute([$title, $body, $id]);
        return (bool) $stmt->rowCount();
    }

    public function deleteThread(int $id): bool {
        // ON DELETE CASCADE en forum.posts se encarga de las respuestas.
        $stmt = $this->pdo->prepare('DELETE FROM forum.threads WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->rowCount();
    }

    public function setPinned(int $id, bool $pinned): bool {
        $stmt = $this->pdo->prepare('UPDATE forum.threads SET is_pinned = ? WHERE id = ?');
        $stmt->execute([$pinned ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    public function setLocked(int $id, bool $locked): bool {
        $stmt = $this->pdo->prepare('UPDATE forum.threads SET is_locked = ? WHERE id = ?');
        $stmt->execute([$locked ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    private function touchThreadActivity(int $threadId): void {
        $this->pdo->prepare(
            'UPDATE forum.threads
             SET reply_count = (SELECT COUNT(*) FROM forum.posts WHERE thread_id = ?),
                 last_post_at = SYSUTCDATETIME()
             WHERE id = ?'
        )->execute([$threadId, $threadId]);
    }

    // -------------------------------------------------------------------------
    // Posts (respuestas)
    // -------------------------------------------------------------------------

    public function getPostsByThread(int $threadId): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM forum.posts WHERE thread_id = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$threadId]);
        return array_map([$this, 'mapPostRow'], $stmt->fetchAll());
    }

    public function getPost(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM forum.posts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->mapPostRow($row) : null;
    }

    public function createPost(int $threadId, string $body, string $account, string $displayName): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.posts (thread_id, author_account, author_display_name, body)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$threadId, $account, $displayName, $body]);
        $id = (int) $stmt->fetchColumn();
        $this->touchThreadActivity($threadId);
        return $id;
    }

    public function editPost(int $id, string $body): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.posts SET body = ?, edited_at = SYSUTCDATETIME() WHERE id = ?'
        );
        $stmt->execute([$body, $id]);
        return (bool) $stmt->rowCount();
    }

    public function deletePost(int $id): bool {
        $post = $this->getPost($id);
        if (!$post) return false;

        $stmt = $this->pdo->prepare('DELETE FROM forum.posts WHERE id = ?');
        $stmt->execute([$id]);
        $ok = (bool) $stmt->rowCount();
        if ($ok) $this->touchThreadActivity((int) $post['thread_id']);
        return $ok;
    }

    // -------------------------------------------------------------------------
    // Reacciones ("Agradecer" — un solo tipo, toggle on/off por cuenta)
    // -------------------------------------------------------------------------

    /** @return array{reacted: bool, count: int} */
    public function toggleReaction(string $targetType, int $targetId, string $account): array {
        $existing = $this->pdo->prepare(
            'SELECT id FROM forum.reactions WHERE target_type = ? AND target_id = ? AND account = ?'
        );
        $existing->execute([$targetType, $targetId, $account]);
        $row = $existing->fetch();

        if ($row) {
            $this->pdo->prepare('DELETE FROM forum.reactions WHERE id = ?')->execute([$row['id']]);
            $reacted = false;
        } else {
            $this->pdo->prepare(
                'INSERT INTO forum.reactions (target_type, target_id, account) VALUES (?, ?, ?)'
            )->execute([$targetType, $targetId, $account]);
            $reacted = true;
        }

        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM forum.reactions WHERE target_type = ? AND target_id = ?'
        );
        $count->execute([$targetType, $targetId]);

        return ['reacted' => $reacted, 'count' => (int) $count->fetchColumn()];
    }

    /**
     * Cuenta de reacciones para varios targets del mismo tipo a la vez.
     * @param int[] $targetIds
     * @return array<int,int> targetId => cantidad
     */
    public function getReactionCounts(string $targetType, array $targetIds): array {
        if (!$targetIds) return [];
        $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT target_id, COUNT(*) AS c FROM forum.reactions
             WHERE target_type = ? AND target_id IN ($placeholders)
             GROUP BY target_id"
        );
        $stmt->execute([$targetType, ...$targetIds]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['target_id']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * Qué targets ya reaccionó esta cuenta, de una lista dada.
     * @param int[] $targetIds
     * @return int[] subconjunto de $targetIds
     */
    public function getUserReactedTargets(string $targetType, array $targetIds, string $account): array {
        if (!$targetIds) return [];
        $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT target_id FROM forum.reactions
             WHERE target_type = ? AND account = ? AND target_id IN ($placeholders)"
        );
        $stmt->execute([$targetType, $account, ...$targetIds]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // -------------------------------------------------------------------------
    // Bans (acotados al foro — nunca tocan la cuenta de juego)
    // -------------------------------------------------------------------------

    public function isBanned(string $account): bool {
        $stmt = $this->pdo->prepare('SELECT 1 FROM forum.banned_accounts WHERE account = ?');
        $stmt->execute([$account]);
        return (bool) $stmt->fetchColumn();
    }

    public function getBan(string $account): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM forum.banned_accounts WHERE account = ?');
        $stmt->execute([$account]);
        return $stmt->fetch() ?: null;
    }

    public function banAccount(string $account, string $bannedBy, ?string $reason): void {
        $stmt = $this->pdo->prepare(
            'MERGE forum.banned_accounts AS target
             USING (SELECT ? AS account) AS src ON target.account = src.account
             WHEN MATCHED THEN UPDATE SET banned_by = ?, reason = ?, banned_at = SYSUTCDATETIME()
             WHEN NOT MATCHED THEN INSERT (account, banned_by, reason) VALUES (?, ?, ?);'
        );
        $stmt->execute([$account, $bannedBy, $reason, $account, $bannedBy, $reason]);
    }

    public function unbanAccount(string $account): bool {
        $stmt = $this->pdo->prepare('DELETE FROM forum.banned_accounts WHERE account = ?');
        $stmt->execute([$account]);
        return (bool) $stmt->rowCount();
    }

    public function getBanHistory(int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} * FROM forum.banned_accounts ORDER BY banned_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
