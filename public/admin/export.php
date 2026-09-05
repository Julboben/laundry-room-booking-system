<?php

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

use LaundryBooking\Auth\AdminAuth;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Response;
use LaundryBooking\Models\ActivityLog;
use LaundryBooking\Http\Request;

$adminAuth = new AdminAuth();

if (!$adminAuth->isAuthenticated()) {
    Response::redirect('/admin/login.php');
}

if ($adminAuth->hasTimedOut()) {
    $adminAuth->logout();
    Response::redirect('/admin/login.php');
}

$adminAuth->touch();

$pdo = Connection::get();

$statement = $pdo->query(
    'SELECT booking_date, slot_key, start_time, end_time, booking_name, created_at
     FROM bookings ORDER BY booking_date, start_time'
);

$activityLog = new ActivityLog($pdo);
$activityLog->log(
    actorType: 'admin',
    action: 'bookings_exported',
    ipAddress: Request::ip(),
    userAgent: Request::userAgent(),
);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="bookings.csv"');

$output = fopen('php://output', 'wb');
fputcsv($output, ['booking_date', 'slot_key', 'start_time', 'end_time', 'booking_name', 'created_at']);

while ($row = $statement->fetch()) {
    fputcsv($output, [
        $row['booking_date'],
        $row['slot_key'],
        $row['start_time'],
        $row['end_time'],
        $row['booking_name'],
        $row['created_at'],
    ]);
}

fclose($output);
