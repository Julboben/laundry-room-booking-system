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
use function LaundryBooking\Support\t;
use function LaundryBooking\Support\tn;

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
$returnDate = Request::get('date') ?? Request::post('return_date') ?? '';
$calendarUrl = DateHelper::isValidDateString($returnDate)
    ? '/calendar.php?date=' . rawurlencode($returnDate)
    : '/calendar.php';
$error = null;
$cancelled = false;

$rateLimiter = new RateLimiter($pdo);
$clientSubject = $id . '|' . Request::ip();
$bookingSubject = (string) $id;

if (Request::isPost()) {
    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = t('Sikkerhedstjekket mislykkedes. Prøv igen.');
    } elseif (
        $rateLimiter->isLocked('cancel_client', $clientSubject)
        || $rateLimiter->isLocked('cancel_booking', $bookingSubject)
    ) {
        $remainingSeconds = max(
            $rateLimiter->remainingSeconds('cancel_client', $clientSubject),
            $rateLimiter->remainingSeconds('cancel_booking', $bookingSubject)
        );
        $minutes = (int) ceil($remainingSeconds / 60);
        $error = tn($minutes, 'For mange forsøg. Prøv igen om ca. %d minut.', 'For mange forsøg. Prøv igen om ca. %d minutter.');
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

            $error = t($result->error);
        }
    }
}

$booking = $id > 0 ? $bookingService->find($id) : null;
$codeLength = $settingsService->getCancellationCodeLength();

layout_start('Aflys booking', bodyClass: 'page-kiosk page-cancel', bodyData: ['idle-return' => $calendarUrl]);
?>
<section class="card kiosk-card cancel-card" aria-labelledby="page-heading">
    <h1 id="page-heading"><?= e(t('Aflys booking')) ?></h1>

    <?php if ($cancelled): ?>
        <p class="alert alert-success" role="status"><?= e(t('Din booking er aflyst.')) ?></p>
        <div class="form-actions">
            <a class="btn btn-primary" href="<?= e($calendarUrl) ?>"><?= e(t('Tilbage til kalenderen')) ?></a>
        </div>
    <?php elseif ($booking === null): ?>
        <p class="alert alert-error" role="alert"><?= e(t('Bookingen findes ikke længere.')) ?></p>
        <div class="form-actions">
            <a class="btn btn-primary" href="<?= e($calendarUrl) ?>"><?= e(t('Tilbage til kalenderen')) ?></a>
        </div>
    <?php else: ?>
        <?php if ($error !== null): ?>
            <p class="alert alert-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <div class="booking-summary is-booked">
            <span class="booking-summary-icon" aria-hidden="true"><?= icon('user') ?></span>
            <div>
                <strong><?= e($booking['booking_name']) ?></strong>
                <span><?= e(ucfirst(danish_date_long(DateHelper::fromDateString($booking['booking_date'])))) ?>, <?= e(t('kl. %s', clock_range($booking['start_time'], $booking['end_time']))) ?></span>
            </div>
        </div>

        <form method="post" action="/cancel.php?id=<?= (int) $id ?>" novalidate>
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <input type="hidden" name="return_date" value="<?= e($returnDate) ?>">
            <label for="cancellation_code"><?= e(t('Aflysningskode')) ?></label>
            <input
                type="text"
                id="cancellation_code"
                name="cancellation_code"
                class="code-input"
                inputmode="numeric"
                autocomplete="off"
                enterkeyhint="done"
                placeholder="<?= e(t('%d cifre', $codeLength)) ?>"
                pattern="\d{<?= (int) $codeLength ?>}"
                minlength="<?= (int) $codeLength ?>"
                maxlength="<?= (int) $codeLength ?>"
                aria-describedby="code-help"
                required
            >
            <small class="form-hint" id="code-help"><?= e(t('Indtast den aflysningskode, du fik, da du bookede tiden.')) ?></small>
            <div class="form-actions">
                <button type="submit" class="btn btn-danger"><?= e(t('Aflys booking')) ?></button>
                <a class="btn btn-secondary" href="<?= e($calendarUrl) ?>"><?= e(t('Tilbage')) ?></a>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php
layout_end();
