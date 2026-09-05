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

$date = Request::get('date', '') ?? '';
$slotKey = Request::get('slot', '') ?? '';
$error = null;
$name = '';

if (Request::isPost()) {
    $date = Request::post('date', '') ?? '';
    $slotKey = Request::post('slot', '') ?? '';
    $name = Request::post('booking_name', '') ?? '';
    $privacyAccepted = Request::post('privacy_ack') === '1';

    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = 'Sikkerhedstjek fejlede. Prøv igen.';
    } elseif (!$privacyAccepted) {
        $error = 'Du skal bekræfte, at du forstår, at navnet vises i kalenderen.';
    } else {
        $result = $bookingService->create(
            $date,
            $slotKey,
            $name,
            Request::ip(),
            Request::userAgent()
        );

        if ($result->success) {
            $_SESSION['booking_success'] = [
                'id' => $result->bookingId,
                'code' => $result->plainCode,
            ];
            Response::redirect('/booking_success.php?id=' . $result->bookingId);
        }

        $error = $result->error;
    }
} else {
    $dateSlotError = $bookingService->validateDateAndSlot($date, $slotKey);
    if ($dateSlotError !== null) {
        $error = $dateSlotError;
    } elseif (!$bookingService->isAvailable($date, $slotKey)) {
        $error = 'Tiden er desværre allerede booket.';
    }
}

$slots = BookingService::slots();
$slot = $slots[$slotKey] ?? null;

layout_start('Book tid');
?>
<section class="card">
    <h2>Book tid</h2>
    <?php if ($error !== null): ?>
        <p class="alert alert-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($slot !== null && DateHelper::isValidDateString($date)): ?>
        <p>
            <?= e(danish_date_long(DateHelper::fromDateString($date))) ?>,
            <?= e($slot['start']) ?>&ndash;<?= e($slot['end']) ?>
        </p>
        <p class="privacy-notice">
            Navnet du skriver, bliver vist i kalenderen for andre beboere, der har adgangskoden til ejendommen.
        </p>
        <form method="post" action="/book.php" novalidate>
            <?= Csrf::field() ?>
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <input type="hidden" name="slot" value="<?= e($slotKey) ?>">

            <label for="booking_name">Navn</label>
            <input
                type="text"
                id="booking_name"
                name="booking_name"
                value="<?= e($name) ?>"
                minlength="2"
                maxlength="100"
                required
            >

            <label class="checkbox-label">
                <input type="checkbox" name="privacy_ack" value="1" required>
                Jeg forstår, at navnet vises i kalenderen for andre beboere med adgang til ejendommens vaskekalender.
            </label>

            <button type="submit" class="btn btn-primary">Gem</button>
            <a class="btn btn-secondary" href="/calendar.php">Tilbage</a>
        </form>
    <?php else: ?>
        <p><a class="btn btn-secondary" href="/calendar.php">Tilbage</a></p>
    <?php endif; ?>
</section>
<?php
layout_end();
