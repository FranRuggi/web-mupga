<?php
/**
 * Acceso a datos del módulo Eventos (mupga_admin.dbo.events / event_registrations).
 * Conexión: AdminDatabase (login mupga_web_svc, datareader+datawriter, sin DDL).
 *
 * Genérico: sirve para cualquier evento (torneos, sorteos, actividades), no
 * solo el Torneo PVP. Fechas siempre en UTC (GETUTCDATE()) — nunca offsets
 * fijos sobre GETDATE(), ver incidente de timezone documentado en CLAUDE.md.
 */
class EventsRepository {

    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    // ── Lectura pública ───────────────────────────────────────

    /**
     * Eventos activos, con el cupo actual. Los sin fecha confirmada
     * ("a confirmar") van primero, después ordenados por fecha ASC.
     */
    public function listActive(): array {
        $stmt = $this->db->query(
            "SELECT e.id, e.title, e.description,
                    CONVERT(VARCHAR(23), e.event_datetime, 126) AS event_datetime,
                    e.max_slots,
                    (SELECT COUNT(*) FROM dbo.event_registrations r WHERE r.event_id = e.id) AS registered_count
             FROM dbo.events e
             WHERE e.is_active = 1
             ORDER BY CASE WHEN e.event_datetime IS NULL THEN 0 ELSE 1 END DESC,
                      e.event_datetime ASC, e.id DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Lista pública de anotados a un evento (solo nombre de personaje,
     * nunca la cuenta — mismo criterio de privacidad que el foro).
     */
    public function listRegistrations(int $eventId): array {
        $stmt = $this->db->prepare(
            "SELECT character_name, CONVERT(VARCHAR(23), created_at, 126) AS created_at
             FROM dbo.event_registrations
             WHERE event_id = ?
             ORDER BY created_at ASC"
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public function getUserRegistration(int $eventId, string $account): ?array {
        $stmt = $this->db->prepare(
            "SELECT character_name FROM dbo.event_registrations WHERE event_id = ? AND account = ?"
        );
        $stmt->execute([$eventId, $account]);
        return $stmt->fetch() ?: null;
    }

    public function getActiveById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, title, description,
                    CONVERT(VARCHAR(23), event_datetime, 126) AS event_datetime,
                    max_slots
             FROM dbo.events WHERE id = ? AND is_active = 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ── Escritura pública (inscripción) ───────────────────────

    /**
     * Inscribe a una cuenta con un personaje. Transacción con
     * UPDLOCK/HOLDLOCK sobre la fila del evento para serializar
     * inscripciones concurrentes (mismo patrón anti-TOCTOU que
     * ProdeRepository::savePrediction() / reclamos rate limit).
     *
     * @throws RuntimeException si el evento no admite inscripciones,
     *         ya está lleno, o la cuenta ya estaba anotada.
     */
    public function register(int $eventId, string $account, string $characterName): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT e.id, e.max_slots,
                        (SELECT COUNT(*) FROM dbo.event_registrations WHERE event_id = e.id) AS registered_count
                 FROM dbo.events e WITH (UPDLOCK, HOLDLOCK)
                 WHERE e.id = ? AND e.is_active = 1
                   AND (e.event_datetime IS NULL OR GETUTCDATE() < e.event_datetime)"
            );
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();

            if (!$event) {
                throw new RuntimeException('El evento no existe o ya no admite inscripciones.');
            }
            if ($event['max_slots'] !== null && (int)$event['registered_count'] >= (int)$event['max_slots']) {
                throw new RuntimeException('El evento ya alcanzó el cupo máximo.');
            }

            $exists = $this->db->prepare(
                "SELECT 1 FROM dbo.event_registrations WHERE event_id = ? AND account = ?"
            );
            $exists->execute([$eventId, $account]);
            if ($exists->fetchColumn()) {
                throw new RuntimeException('Ya estás anotado a este evento.');
            }

            $insert = $this->db->prepare(
                "INSERT INTO dbo.event_registrations (event_id, account, character_name, created_at)
                 VALUES (?, ?, ?, GETUTCDATE())"
            );
            $insert->execute([$eventId, $account, $characterName]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function unregister(int $eventId, string $account): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM dbo.event_registrations WHERE event_id = ? AND account = ?"
        );
        $stmt->execute([$eventId, $account]);
        return (bool) $stmt->rowCount();
    }

    // ── Admin ──────────────────────────────────────────────────

    /**
     * Lista de anotados para el ControlPanel: incluye la cuenta (memb___id),
     * a diferencia de listRegistrations() (pública, solo nombre de personaje).
     * Necesaria para que el admin pueda dar de baja una inscripción puntual.
     */
    public function listRegistrationsAdmin(int $eventId): array {
        $stmt = $this->db->prepare(
            "SELECT account, character_name, CONVERT(VARCHAR(23), created_at, 126) AS created_at
             FROM dbo.event_registrations
             WHERE event_id = ?
             ORDER BY created_at ASC"
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    /**
     * Todos los eventos (incluidos los inactivos), para el ControlPanel.
     */
    public function listAll(): array {
        $stmt = $this->db->query(
            "SELECT e.id, e.title, e.description,
                    CONVERT(VARCHAR(23), e.event_datetime, 126) AS event_datetime,
                    e.max_slots, e.is_active, e.created_by,
                    CONVERT(VARCHAR(23), e.created_at, 126) AS created_at,
                    (SELECT COUNT(*) FROM dbo.event_registrations r WHERE r.event_id = e.id) AS registered_count
             FROM dbo.events e
             ORDER BY e.id DESC"
        );
        return $stmt->fetchAll();
    }

    public function create(
        string  $title,
        ?string $description,
        ?string $eventDatetimeUtc,
        ?int    $maxSlots,
        string  $createdBy
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO dbo.events (title, description, event_datetime, max_slots, is_active, created_by, created_at, updated_at)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?, 1, ?, GETUTCDATE(), GETUTCDATE())"
        );
        $stmt->execute([$title, $description, $eventDatetimeUtc, $maxSlots, $createdBy]);
        return (int) $stmt->fetchColumn();
    }

    public function update(
        int     $id,
        string  $title,
        ?string $description,
        ?string $eventDatetimeUtc,
        ?int    $maxSlots
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE dbo.events
             SET title = ?, description = ?, event_datetime = ?, max_slots = ?, updated_at = GETUTCDATE()
             WHERE id = ?"
        );
        $stmt->execute([$title, $description, $eventDatetimeUtc, $maxSlots, $id]);
        return (bool) $stmt->rowCount();
    }

    public function setActive(int $id, bool $active): bool {
        $stmt = $this->db->prepare(
            "UPDATE dbo.events SET is_active = ?, updated_at = GETUTCDATE() WHERE id = ?"
        );
        $stmt->execute([$active ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    /**
     * Da de baja la inscripción de una cuenta puntual (moderación manual,
     * ej. anotado por error o de mala fe).
     */
    public function removeRegistration(int $eventId, string $account): bool {
        return $this->unregister($eventId, $account);
    }
}
