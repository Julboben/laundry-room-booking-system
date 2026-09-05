<?php

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';
require __DIR__ . '/../../app/View/layout.php';

use LaundryBooking\Auth\AdminAuth;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Csrf;
use LaundryBooking\Http\Request;
use LaundryBooking\Http\Response;
use LaundryBooking\Models\ActivityLog;
use LaundryBooking\Support\DateHelper;

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

$error = null;
$success = null;

if (Request::isPost()) {
    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = 'Sikkerhedstjek fejlede. Prøv igen.';
    } else {
        $deleteId = (int) (Request::post('delete_id') ?? '0');

        if ($deleteId > 0) {
            $pdo->beginTransaction();

            try {
                $statement = $pdo->prepare('DELETE FROM bookings WHERE id = :id');
                $statement->execute(['id' => $deleteId]);

                if ($statement->rowCount() === 0) {
                    $pdo->rollBack();
                    $error = 'Bookingen findes ikke.';
                } else {
                    $activityLog->log(
                        actorType: 'admin',
                        action: 'booking_deleted',
                        bookingId: $deleteId,
                        ipAddress: Request::ip(),
                        userAgent: Request::userAgent(),
                        details: 'Deleted by ' . ($adminAuth->currentUsername() ?? 'unknown')
                    );
                    $pdo->commit();
                    $success = 'Bookingen er slettet.';
                }
            } catch (\Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $exception;
            }
        }
    }
}

$dateFrom = Request::get('date_from', '') ?? '';
$dateTo = Request::get('date_to', '') ?? '';
$search = Request::get('search', '') ?? '';

$conditions = [];
$params = [];

if (DateHelper::isValidDateString($dateFrom)) {
    $conditions[] = 'booking_date >= :date_from';
    $params['date_from'] = $dateFrom;
}

if (DateHelper::isValidDateString($dateTo)) {
    $conditions[] = 'booking_date <= :date_to';
    $params['date_to'] = $dateTo;
}

if (trim($search) !== '') {
    $conditions[] = 'booking_name LIKE :search';
    $params['search'] = '%' . trim($search) . '%';
}

$where = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

$statement = $pdo->prepare(
    "SELECT id, booking_date, slot_key, start_time, end_time, booking_name, created_at
     FROM bookings {$where}
     ORDER BY booking_date DESC, start_time DESC
     LIMIT 500"
);
$statement->execute($params);
$bookings = $statement->fetchAll();

layout_start('Bookinger');
?>
<section class="card">
    <h2>Bookinger</h2>
    <p><a href="/admin/index.php">&laquo; Tilbage til administration</a></p>

    <?php if ($error !== null): ?>
        <p class="alert alert-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <?php if ($success !== null): ?>
        <p class="alert alert-success" role="status"><?= e($success) ?></p>
    <?php endif; ?>

    <form method="get" action="/admin/bookings.php" class="filter-form">
        <label for="date_from">Fra dato</label>
        <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>">

        <label for="date_to">Til dato</label>
        <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>">

        <label for="search">Søg navn</label>
        <input type="text" id="search" name="search" value="<?= e($search) ?>">

        <button type="submit" class="btn btn-secondary">Filtrer</button>
    </form>

    <table class="bookings-table">
        <thead>
        <tr>
            <th>Dato</th>
            <th>Tid</th>
            <th>Navn</th>
            <th>Oprettet</th>
            <th>Handling</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?= e($booking['booking_date']) ?></td>
                <td><?= e(substr($booking['start_time'], 0, 5)) ?>&ndash;<?= e(substr($booking['end_time'], 0, 5)) ?></td>
                <td><?= e($booking['booking_name']) ?></td>
                <td><?= e($booking['created_at']) ?></td>
                <td>
                    <form method="post" action="/admin/bookings.php" data-confirm="Slet denne booking?">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="delete_id" value="<?= (int) $booking['id'] ?>">
                        <button type="submit" class="btn btn-danger">Slet</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($bookings === []): ?>
            <tr><td colspan="5">Ingen bookinger fundet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php
layout_end();
