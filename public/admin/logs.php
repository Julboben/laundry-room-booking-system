<?php

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';
require __DIR__ . '/../../app/View/layout.php';

use LaundryBooking\Auth\AdminAuth;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Response;
use LaundryBooking\Models\ActivityLog;

use function LaundryBooking\Support\e;

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
$activityLog = new ActivityLog($pdo);
$logs = $activityLog->recent(200);

layout_start('Aktivitetslog');
?>
<section class="card">
    <h2>Aktivitetslog</h2>
    <p><a href="/admin/index.php">&laquo; Tilbage til administration</a></p>

    <table class="bookings-table">
        <thead>
        <tr>
            <th>Tidspunkt</th>
            <th>Aktør</th>
            <th>Handling</th>
            <th>Booking-id</th>
            <th>IP</th>
            <th>Detaljer</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= e((string) $log['created_at']) ?></td>
                <td><?= e((string) $log['actor_type']) ?></td>
                <td><?= e((string) $log['action']) ?></td>
                <td><?= $log['booking_id'] !== null ? (int) $log['booking_id'] : '' ?></td>
                <td><?= e((string) ($log['ip_address'] ?? '')) ?></td>
                <td><?= e((string) ($log['details'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($logs === []): ?>
            <tr><td colspan="6">Ingen aktivitet endnu.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php
layout_end();
