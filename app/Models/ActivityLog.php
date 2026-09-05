<?php

declare(strict_types=1);

namespace LaundryBooking\Models;

use PDO;

/**
 * Data access for the activity_logs table. Never store cancellation
 * codes or other secrets in the details column.
 */
final class ActivityLog
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function log(
        string $actorType,
        string $action,
        ?int $bookingId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $details = null
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO activity_logs (actor_type, action, booking_id, ip_address, user_agent, details)
             VALUES (:actor_type, :action, :booking_id, :ip_address, :user_agent, :details)'
        );

        $statement->execute([
            'actor_type' => $actorType,
            'action' => $action,
            'booking_id' => $bookingId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'details' => $details,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function recent(int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $statement = $this->pdo->query(
            'SELECT id, actor_type, action, booking_id, ip_address, user_agent, details, created_at
             FROM activity_logs ORDER BY created_at DESC, id DESC LIMIT ' . $limit
        );

        return $statement !== false ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}
