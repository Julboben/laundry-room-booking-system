<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use LaundryBooking\Database\Connection;
use LaundryBooking\Models\ActivityLog;
use LaundryBooking\Services\CleanupService;
use LaundryBooking\Support\Env;

Env::load(__DIR__ . '/../.env');

$pdo = Connection::get();
$cleanupService = new CleanupService($pdo, new ActivityLog($pdo));

$bookingRetentionDays = Env::getInt('OLD_BOOKING_RETENTION_DAYS', 180);

$deletedBookings = $cleanupService->cleanupOldBookings($bookingRetentionDays);
$deletedLogs = $cleanupService->cleanupOldLogs($bookingRetentionDays);

echo "Deleted {$deletedBookings} old booking(s).\n";
echo "Deleted {$deletedLogs} old activity log entr" . ($deletedLogs === 1 ? 'y' : 'ies') . ".\n";
