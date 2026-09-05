<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/View/layout.php';

use LaundryBooking\Auth\ResidentAccess;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Csrf;
use LaundryBooking\Http\Request;
use LaundryBooking\Http\Response;
use LaundryBooking\Models\ActivityLog;
use LaundryBooking\Models\Setting;
use LaundryBooking\Security\RateLimiter;
use LaundryBooking\Services\BookingService;
use LaundryBooking\Services\CodeService;
use LaundryBooking\Services\SettingsService;
use LaundryBooking\Support\DateHelper;

use function LaundryBooking\Support\e;

$pdo = Connection::get();
$settingsService = new SettingsService(new Setting($pdo));
$residentAccess = new ResidentAccess($settingsService);

if (!$residentAccess->isAuthenticated()) {
    Response::redirect('/access.php');
}

$bookingService = new BookingService($pdo, new CodeService(), $settingsService, new ActivityLog($pdo));

const MAX_CANCEL_CLIENT_ATTEMPTS = 5;
const MAX_CANCEL_BOOKING_ATTEMPTS = 25;
const CANCEL_LOCKOUT_SECONDS = 300;

$id = (int) (Request::get('id') ?? Request::post('id') ?? '0');
$error = null;
$cancelled = false;

$rateLimiter = new RateLimiter($pdo);
$clientSubject = $id . '|' . Request::ip();
$bookingSubject = (string) $id;

if (Request::isPost()) {
    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = 'Sikkerhedstjek fejlede. Prøv igen.';
    } elseif (
        $rateLimiter->isLocked('cancel_client', $clientSubject)
        || $rateLimiter->isLocked('cancel_booking', $bookingSubject)
    ) {
        $remainingSeconds = max(
            $rateLimiter->remainingSeconds('cancel_client', $clientSubject),
            $rateLimiter->remainingSeconds('cancel_booking', $bookingSubject)
        );
        $minutes = (int) ceil($remainingSeconds / 60);
        $error = "For mange forsøg. Prøv igen om ca. {$minutes} minutter.";
    } else {
        $code = Request::post('cancellation_code', '') ?? '';

        $result = $bookingService->cancel($id, $code, Request::ip(), Request::userAgent());

        if ($result->success) {
            $rateLimiter->clear('cancel_client', $clientSubject);
            $rateLimiter->clear('cancel_booking', $bookingSubject);
            $cancelled = true;
        } else {
            $rateLimiter->recordFailure(
                'cancel_client',
                $clientSubject,
                MAX_CANCEL_CLIENT_ATTEMPTS,
                CANCEL_LOCKOUT_SECONDS,
                CANCEL_LOCKOUT_SECONDS
            );
            $rateLimiter->recordFailure(
                'cancel_booking',
                $bookingSubject,
                MAX_CANCEL_BOOKING_ATTEMPTS,
                CANCEL_LOCKOUT_SECONDS,
                CANCEL_LOCKOUT_SECONDS
            );

            $error = $result->error;
        }
    }
}

$booking = $id > 0 ? $bookingService->find($id) : null;

layout_start('Aflys booking');
?>
<section class="card">
    <h2>Aflys booking</h2>

    <?php if ($cancelled): ?>
        <p class="alert alert-success" role="status">Bookingen er blevet fjernet.</p>
        <p><a class="btn btn-primary" href="/calendar.php">Tilbage til kalenderen</a></p>
    <?php elseif ($booking === null): ?>
        <p class="alert alert-error" role="alert">Bookingen findes ikke.</p>
        <p><a class="btn btn-secondary" href="/calendar.php">Tilbage</a></p>
    <?php else: ?>
        <?php if ($error !== null): ?>
            <p class="alert alert-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <p>
            <?= e(danish_date_long(DateHelper::fromDateString($booking['booking_date']))) ?>,
            <?= e(substr($booking['start_time'], 0, 5)) ?>&ndash;<?= e(substr($booking['end_time'], 0, 5)) ?>
        </p>
        <p>Navn: <?= e($booking['booking_name']) ?></p>

        <p>Bookingen bliver fjernet, hvis koden er korrekt.</p>

        <form method="post" action="/cancel.php?id=<?= (int) $id ?>" novalidate>
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <label for="cancellation_code">Aflysningskode</label>
            <input
                type="text"
                id="cancellation_code"
                name="cancellation_code"
                autocapitalize="characters"
                pattern="(\d{6}|[2-9A-HJ-NP-Z]{4}-?[2-9A-HJ-NP-Z]{4}-?[2-9A-HJ-NP-Z]{4}-?[2-9A-HJ-NP-Z]{4})"
                maxlength="19"
                required
            >
            <button type="submit" class="btn btn-primary">Aflys booking</button>
            <a class="btn btn-secondary" href="/calendar.php">Tilbage</a>
        </form>
    <?php endif; ?>
</section>
<?php
layout_end();
