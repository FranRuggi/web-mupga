<?php
/**
 * Acceso a datos del foro (schema forum en mupga_admin, vía ForumDatabase).
 * Nunca toca datos de juego — el nombre de personaje se resuelve aparte
 * (CharacterRepository, conexión principal) y se guarda denormalizado acá
 * (author_display_name) al momento de postear, mismo criterio que
 * reclamos.reclamos.nick.
 *
 * Borrado: SIEMPRE soft delete (deleted_at/deleted_by) — el DELETE físico
 * no existe en este módulo desde la migración v2. Los listados públicos
 * filtran deleted_at IS NULL; la papelera del ControlPanel los recupera.
 */
class ForumRepository {

    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // -------------------------------------------------------------------------
    // Mapeo de tipos
    // -------------------------------------------------------------------------

    /**
     * SQL Server devuelve BIT vía sqlsrv como string ("0"/"1"), no bool — y "0" es
     * un string TRUTHY en JS. Sin este cast, cualquier checkbox lee como tildado
     * siempre en el frontend. Cast explícito acá, una sola vez, para que todo lo
     * que sale de este repositorio hacia el JSON tenga tipos reales.
     */
    private function mapCategoryRow(array $row): array {
        $row['id']              = (int) $row['id'];
        $row['sort_order']      = (int) $row['sort_order'];
        $row['admin_only_post'] = (bool) (int) $row['admin_only_post'];
        if (array_key_exists('is_hidden', $row))    $row['is_hidden']    = (bool) (int) $row['is_hidden'];
        if (array_key_exists('thread_count', $row)) $row['thread_count'] = (int) $row['thread_count'];
        return $row;
    }

    private function mapThreadRow(array $row): array {
        $row['id']          = (int) $row['id'];
        $row['category_id'] = (int) $row['category_id'];
        $row['is_pinned']   = (bool) (int) $row['is_pinned'];
        $row['is_locked']   = (bool) (int) $row['is_locked'];
        if (isset($row['reply_count'])) $row['reply_count'] = (int) $row['reply_count'];
        if (array_key_exists('edited_by_staff', $row)) $row['edited_by_staff'] = (bool) (int) $row['edited_by_staff'];
        if (array_key_exists('deleted_at', $row))      $row['is_deleted']      = $row['deleted_at'] !== null;
        return $row;
    }

    private function mapPostRow(array $row): array {
        $row['id']        = (int) $row['id'];
        $row['thread_id'] = (int) $row['thread_id'];
        if (array_key_exists('edited_by_staff', $row)) $row['edited_by_staff'] = (bool) (int) $row['edited_by_staff'];
        if (array_key_exists('deleted_at', $row))      $row['is_deleted']      = $row['deleted_at'] !== null;
        return $row;
    }

    // -------------------------------------------------------------------------
    // Categorías
    // -------------------------------------------------------------------------

    /**
     * Listado con conteo de hilos y última actividad (F-10.01). Las ocultas
     * solo se incluyen si $includeHidden (admins).
     */
    public function getCategories(bool $includeHidden = false): array {
        $where = $includeHidden ? '' : 'WHERE c.is_hidden = 0';
        $stmt = $this->pdo->query(
            "SELECT c.id, c.name, c.slug, c.description, c.sort_order,
                    c.admin_only_post, c.is_hidden,
                    (SELECT COUNT(*) FROM forum.threads t
                      WHERE t.category_id = c.id AND t.deleted_at IS NULL) AS thread_count,
                    (SELECT MAX(t.last_post_at) FROM forum.threads t
                      WHERE t.category_id = c.id AND t.deleted_at IS NULL) AS last_activity_at
             FROM forum.categories c
             {$where}
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        return array_map([$this, 'mapCategoryRow'], $stmt->fetchAll());
    }

    public function getCategory(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM forum.categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->mapCategoryRow($row) : null;
    }

    public function createCategory(string $name, string $slug, ?string $description, int $sortOrder, bool $adminOnlyPost, bool $isHidden = false): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post, is_hidden)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $slug, $description, $sortOrder, $adminOnlyPost ? 1 : 0, $isHidden ? 1 : 0]);
        return (int) $stmt->fetchColumn();
    }

    public function updateCategory(int $id, string $name, ?string $description, int $sortOrder, bool $adminOnlyPost, bool $isHidden = false): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.categories
             SET name = ?, description = ?, sort_order = ?, admin_only_post = ?, is_hidden = ?
             WHERE id = ?'
        );
        $stmt->execute([$name, $description, $sortOrder, $adminOnlyPost ? 1 : 0, $isHidden ? 1 : 0, $id]);
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
                    is_pinned, is_locked, locked_reason, reply_count, created_at, last_post_at,
                    edited_by_staff, deleted_at
             FROM forum.threads
             WHERE category_id = ? AND deleted_at IS NULL
             ORDER BY is_pinned DESC, last_post_at DESC"
        );
        $stmt->execute([$categoryId]);
        return array_map([$this, 'mapThreadRow'], $stmt->fetchAll());
    }

    /** Devuelve el hilo incluso si está borrado — el caller decide qué hacer. */
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

    public function editThread(int $id, string $title, string $body, bool $byStaff = false): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads
             SET title = ?, body = ?, edited_at = SYSUTCDATETIME(), edited_by_staff = ?
             WHERE id = ?'
        );
        $stmt->execute([$title, $body, $byStaff ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    public function softDeleteThread(int $id, string $byAccount): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads
             SET deleted_at = SYSUTCDATETIME(), deleted_by = ?
             WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$byAccount, $id]);
        return (bool) $stmt->rowCount();
    }

    public function restoreThread(int $id): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads SET deleted_at = NULL, deleted_by = NULL WHERE id = ?'
        );
        $stmt->execute([$id]);
        return (bool) $stmt->rowCount();
    }

    public function getDeletedThreads(int $limit = 30): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} id, category_id, title, author_display_name,
                    deleted_at, deleted_by, created_at, is_pinned, is_locked, reply_count
             FROM forum.threads
             WHERE deleted_at IS NOT NULL
             ORDER BY deleted_at DESC"
        );
        $stmt->execute();
        return array_map([$this, 'mapThreadRow'], $stmt->fetchAll());
    }

    public function setPinned(int $id, bool $pinned): bool {
        $stmt = $this->pdo->prepare('UPDATE forum.threads SET is_pinned = ? WHERE id = ?');
        $stmt->execute([$pinned ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    public function setLocked(int $id, bool $locked, ?string $reason = null): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads SET is_locked = ?, locked_reason = ? WHERE id = ?'
        );
        $stmt->execute([$locked ? 1 : 0, $locked ? $reason : null, $id]);
        return (bool) $stmt->rowCount();
    }

    public function moveThread(int $id, int $categoryId): bool {
        $stmt = $this->pdo->prepare('UPDATE forum.threads SET category_id = ? WHERE id = ?');
        $stmt->execute([$categoryId, $id]);
        return (bool) $stmt->rowCount();
    }

    /**
     * ¿El hilo tiene respuestas (visibles) de alguien que no sea $account?
     * Regla F-02.03: el autor solo puede borrar su hilo si nadie más participó.
     */
    public function threadHasRepliesByOthers(int $threadId, string $account): bool {
        $stmt = $this->pdo->prepare(
            'SELECT TOP 1 1 FROM forum.posts
             WHERE thread_id = ? AND author_account <> ? AND deleted_at IS NULL'
        );
        $stmt->execute([$threadId, $account]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Recalcula reply_count y last_post_at contando solo respuestas visibles.
     * last_post_at cae al created_at del hilo si no queda ninguna respuesta —
     * así borrar la última respuesta no deja el hilo "subido" artificialmente.
     */
    private function touchThreadActivity(int $threadId): void {
        $this->pdo->prepare(
            'UPDATE forum.threads
             SET reply_count = (SELECT COUNT(*) FROM forum.posts
                                WHERE thread_id = ? AND deleted_at IS NULL),
                 last_post_at = COALESCE(
                     (SELECT MAX(created_at) FROM forum.posts
                      WHERE thread_id = ? AND deleted_at IS NULL),
                     created_at)
             WHERE id = ?'
        )->execute([$threadId, $threadId, $threadId]);
    }

    // -------------------------------------------------------------------------
    // Posts (respuestas)
    // -------------------------------------------------------------------------

    /** Incluye borradas (con is_deleted=true) — el endpoint decide cómo mostrarlas. */
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

    public function editPost(int $id, string $body, bool $byStaff = false): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.posts
             SET body = ?, edited_at = SYSUTCDATETIME(), edited_by_staff = ?
             WHERE id = ?'
        );
        $stmt->execute([$body, $byStaff ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    public function softDeletePost(int $id, string $byAccount): bool {
        $post = $this->getPost($id);
        if (!$post || $post['is_deleted']) return false;

        $stmt = $this->pdo->prepare(
            'UPDATE forum.posts
             SET deleted_at = SYSUTCDATETIME(), deleted_by = ?
             WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$byAccount, $id]);
        $ok = (bool) $stmt->rowCount();
        if ($ok) $this->touchThreadActivity((int) $post['thread_id']);
        return $ok;
    }

    public function restorePost(int $id): bool {
        $post = $this->getPost($id);
        if (!$post) return false;
        $stmt = $this->pdo->prepare(
            'UPDATE forum.posts SET deleted_at = NULL, deleted_by = NULL WHERE id = ?'
        );
        $stmt->execute([$id]);
        $ok = (bool) $stmt->rowCount();
        if ($ok) $this->touchThreadActivity((int) $post['thread_id']);
        return $ok;
    }

    // -------------------------------------------------------------------------
    // Antiflood (F-09.01) — evaluado server-side contra la base
    // -------------------------------------------------------------------------

    /**
     * Actividad reciente de publicación de una cuenta (hilos + respuestas,
     * borradas incluidas — las publicó igual). Llamar DENTRO de una transacción:
     * los hints UPDLOCK/HOLDLOCK serializan dos requests simultáneos de la
     * misma cuenta (mismo patrón anti-TOCTOU que reclamos/create.php).
     *
     * @return array{seconds_since_last: ?int, count_last_hour: int}
     */
    public function getPostingThrottle(string $account): array {
        $stmt = $this->pdo->prepare(
            'SELECT
                (SELECT DATEDIFF(SECOND, MAX(x.c), GETUTCDATE()) FROM (
                    SELECT MAX(created_at) AS c FROM forum.threads WITH (UPDLOCK, HOLDLOCK)
                     WHERE author_account = :a1
                    UNION ALL
                    SELECT MAX(created_at) FROM forum.posts WITH (UPDLOCK, HOLDLOCK)
                     WHERE author_account = :a2
                ) x) AS seconds_since_last,
                (SELECT COUNT(*) FROM forum.threads
                  WHERE author_account = :a3 AND created_at > DATEADD(HOUR, -1, GETUTCDATE()))
              + (SELECT COUNT(*) FROM forum.posts
                  WHERE author_account = :a4 AND created_at > DATEADD(HOUR, -1, GETUTCDATE()))
                AS count_last_hour'
        );
        $stmt->execute([':a1' => $account, ':a2' => $account, ':a3' => $account, ':a4' => $account]);
        $row = $stmt->fetch();
        return [
            'seconds_since_last' => $row['seconds_since_last'] !== null ? (int) $row['seconds_since_last'] : null,
            'count_last_hour'    => (int) $row['count_last_hour'],
        ];
    }

    /** Publicaciones visibles totales de la cuenta (para F-09.03, links de cuentas nuevas). */
    public function countPostsByAccount(string $account): int {
        $stmt = $this->pdo->prepare(
            'SELECT (SELECT COUNT(*) FROM forum.threads WHERE author_account = :a1 AND deleted_at IS NULL)
                  + (SELECT COUNT(*) FROM forum.posts   WHERE author_account = :a2 AND deleted_at IS NULL)'
        );
        $stmt->execute([':a1' => $account, ':a2' => $account]);
        return (int) $stmt->fetchColumn();
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
    // Reportes (F-08.02 / F-08.03)
    // -------------------------------------------------------------------------

    public function createReport(string $targetType, int $targetId, string $reporter, string $reasonCode, ?string $comment): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.reports (target_type, target_id, reporter_account, reason_code, comment)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$targetType, $targetId, $reporter, $reasonCode, $comment]);
        return (int) $stmt->fetchColumn();
    }

    public function hasReported(string $targetType, int $targetId, string $reporter): bool {
        $stmt = $this->pdo->prepare(
            'SELECT TOP 1 1 FROM forum.reports
             WHERE target_type = ? AND target_id = ? AND reporter_account = ?'
        );
        $stmt->execute([$targetType, $targetId, $reporter]);
        return (bool) $stmt->fetchColumn();
    }

    public function countRecentReportsByAccount(string $account): int {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM forum.reports
             WHERE reporter_account = ? AND created_at > DATEADD(HOUR, -1, GETUTCDATE())'
        );
        $stmt->execute([$account]);
        return (int) $stmt->fetchColumn();
    }

    /** Reportes pendientes, más viejos primero, con contexto del contenido reportado. */
    public function getPendingReports(int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} r.id, r.target_type, r.target_id, r.reporter_account,
                    r.reason_code, r.comment, r.created_at,
                    COALESCE(t.title, LEFT(p.body, 120))      AS target_excerpt,
                    COALESCE(t.author_display_name, p.author_display_name) AS target_author,
                    COALESCE(t.id, p.thread_id)               AS thread_id,
                    CASE WHEN COALESCE(t.deleted_at, p.deleted_at) IS NULL THEN 0 ELSE 1 END AS target_deleted
             FROM forum.reports r
             LEFT JOIN forum.threads t ON r.target_type = 'thread' AND t.id = r.target_id
             LEFT JOIN forum.posts   p ON r.target_type = 'post'   AND p.id = r.target_id
             WHERE r.status = 'pendiente'
             ORDER BY r.created_at ASC"
        );
        $stmt->execute();
        return array_map(function ($row) {
            $row['id']             = (int) $row['id'];
            $row['target_id']      = (int) $row['target_id'];
            $row['thread_id']      = $row['thread_id'] !== null ? (int) $row['thread_id'] : null;
            $row['target_deleted'] = (bool) (int) $row['target_deleted'];
            return $row;
        }, $stmt->fetchAll());
    }

    /**
     * Resuelve TODOS los reportes pendientes sobre el mismo contenido de una
     * (F-08.03: cerrar juntos). Devuelve cuántos cerró.
     */
    public function resolveReportsForTarget(string $targetType, int $targetId, string $status, string $resolvedBy, ?string $note): int {
        $stmt = $this->pdo->prepare(
            "UPDATE forum.reports
             SET status = ?, resolved_by = ?, resolved_note = ?, resolved_at = SYSUTCDATETIME()
             WHERE target_type = ? AND target_id = ? AND status = 'pendiente'"
        );
        $stmt->execute([$status, $resolvedBy, $note, $targetType, $targetId]);
        return $stmt->rowCount();
    }

    // -------------------------------------------------------------------------
    // Log de auditoría (F-08.05)
    // -------------------------------------------------------------------------

    public function logModeration(string $actor, string $action, string $targetType, string $targetId, ?string $reason = null, ?string $bodyBefore = null): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.moderation_log (actor_account, action, target_type, target_id, reason, body_before)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$actor, $action, $targetType, $targetId, $reason, $bodyBefore]);
    }

    public function getModerationLog(int $limit = 100): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} id, actor_account, action, target_type, target_id, reason, created_at
             FROM forum.moderation_log ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // Bans (acotados al foro — nunca tocan la cuenta de juego)
    // -------------------------------------------------------------------------

    /** Solo bans VIGENTES (sin vencimiento, o con vencimiento futuro). */
    public function getBan(string $account): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM forum.banned_accounts
             WHERE account = ? AND (expires_at IS NULL OR expires_at > GETUTCDATE())'
        );
        $stmt->execute([$account]);
        return $stmt->fetch() ?: null;
    }

    public function isBanned(string $account): bool {
        return $this->getBan($account) !== null;
    }

    /** @param int|null $expiresDays null = permanente */
    public function banAccount(string $account, string $bannedBy, ?string $reason, ?int $expiresDays = null): void {
        $update = $this->pdo->prepare(
            'UPDATE forum.banned_accounts
             SET banned_by = :by, reason = :reason, banned_at = SYSUTCDATETIME(),
                 expires_at = CASE WHEN :days IS NULL THEN NULL
                                   ELSE DATEADD(DAY, :days2, GETUTCDATE()) END
             WHERE account = :acc'
        );
        $update->execute([':by' => $bannedBy, ':reason' => $reason, ':days' => $expiresDays, ':days2' => $expiresDays, ':acc' => $account]);

        if ($update->rowCount() === 0) {
            $insert = $this->pdo->prepare(
                'INSERT INTO forum.banned_accounts (account, banned_by, reason, expires_at)
                 VALUES (:acc, :by, :reason,
                         CASE WHEN :days IS NULL THEN NULL
                              ELSE DATEADD(DAY, :days2, GETUTCDATE()) END)'
            );
            $insert->execute([':acc' => $account, ':by' => $bannedBy, ':reason' => $reason, ':days' => $expiresDays, ':days2' => $expiresDays]);
        }
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
