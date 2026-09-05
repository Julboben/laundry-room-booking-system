<?php

declare(strict_types=1);

namespace LaundryBooking\Services;

use LaundryBooking\Models\ActivityLog;
use PDO;

/**
 * Deletes old bookings and activity logs based on retention settings.
 */
final class CleanupService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ActivityLog $activityLog,
    ) {
    }

    public function cleanupOldBookings(int $retentionDays): int
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM bookings WHERE booking_date < (CURDATE() - INTERVAL :days DAY)'
        );
        $statement->bindValue('days', $retentionDays, PDO::PARAM_INT);
        $statement->execute();

        $deleted = $statement->rowCount();

        $this->activityLog->log(
            actorType: 'system',
            action: 'cleanup_old_bookings',
            details: sprintf('Deleted %d booking(s) older than %d days.', $deleted, $retentionDays)
        );

        return $deleted;
    }

    public function cleanupOldLogs(int $retentionDays): int
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM activity_logs WHERE created_at < (CURDATE() - INTERVAL :days DAY)'
        );
        $statement->bindValue('days', $retentionDays, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount();
    }
}
