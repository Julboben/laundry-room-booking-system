<?php

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';
require __DIR__ . '/../../app/View/layout.php';

use LaundryBooking\Auth\AdminAuth;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Response;

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

$totalBookings = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$bookingsToday = (int) $pdo->query('SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()')->fetchColumn();
$bookingsNext7Days = (int) $pdo->query(
    'SELECT COUNT(*) FROM bookings WHERE booking_date BETWEEN CURDATE() AND (CURDATE() + INTERVAL 7 DAY)'
)->fetchColumn();

$lastCleanupStatement = $pdo->query(
    "SELECT created_at FROM activity_logs WHERE action = 'cleanup_old_bookings' ORDER BY created_at DESC LIMIT 1"
);
$lastCleanup = $lastCleanupStatement->fetchColumn();

$databaseStatus = 'ok';
try {
    $pdo->query('SELECT 1');
} catch (\Throwable) {
    $databaseStatus = 'error';
}

layout_start('Administration');
?>
<section class="card">
    <h2>Administration</h2>
    <nav class="admin-nav">
        <a href="/admin/bookings.php">Bookinger</a>
        <a href="/admin/settings.php">Indstillinger</a>
        <a href="/admin/logs.php">Logs</a>
        <a href="/admin/export.php">Eksport (CSV)</a>
        <a href="/admin/logout.php">Log ud</a>
    </nav>

    <dl class="dashboard-stats">
        <dt>Bookinger i alt</dt>
        <dd><?= $totalBookings ?></dd>

        <dt>Bookinger i dag</dt>
        <dd><?= $bookingsToday ?></dd>

        <dt>Bookinger de næste 7 dage</dt>
        <dd><?= $bookingsNext7Days ?></dd>

        <dt>Seneste oprydning</dt>
        <dd><?= $lastCleanup !== false ? e((string) $lastCleanup) : 'Ukendt' ?></dd>

        <dt>PHP-version</dt>
        <dd><?= e(PHP_VERSION) ?></dd>

        <dt>Database</dt>
        <dd><?= e($databaseStatus) ?></dd>
    </dl>
</section>
<?php
layout_end();
