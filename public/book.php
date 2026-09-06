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

    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = 'Sikkerhedstjek fejlede. Prøv igen.';
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
$takeoverMinutes = $settingsService->getTakeoverRuleMinutes();

layout_start('Bekræft booking', bodyClass: 'page-kiosk page-booking');
?>
<section class="card kiosk-card booking-card">
    <h2>Bekræft din tid</h2>
    <?php if ($error !== null): ?>
        <p class="alert alert-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($slot !== null && DateHelper::isValidDateString($date)): ?>
        <div class="booking-summary">
            <span class="booking-summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z"></path><path d="M8 13h3v3H8z"></path></svg>
            </span>
            <div>
                <strong><?= e(ucfirst(danish_date_long(DateHelper::fromDateString($date)))) ?></strong>
                <span><?= e($slot['start']) ?>&ndash;<?= e($slot['end']) ?></span>
            </div>
        </div>
        <form method="post" action="/book.php" novalidate>
            <?= Csrf::field() ?>
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <input type="hidden" name="slot" value="<?= e($slotKey) ?>">

            <label for="booking_name">Dit navn</label>
            <input
                type="text"
                id="booking_name"
                name="booking_name"
                value="<?= e($name) ?>"
                minlength="2"
                maxlength="100"
                aria-describedby="name-help"
                required
            >
            <small class="form-hint" id="name-help">Navnet vises på den bookede tid.</small>

            <div class="booking-notes">
                <h3>Godt at vide</h3>
                <ul>
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                        <span>Er tiden ikke taget i brug senest <?= (int) $takeoverMinutes ?> minutter efter start, må en anden beboer overtage den.</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4"></circle><path d="m11 12 8-8M16 7l3 3"></path></svg>
                        <span>Efter bookingen får du en aflysningskode. Gem den, hvis du får brug for at aflyse.</span>
                    </li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary">Bekræft booking</button>
            <a class="btn btn-secondary" href="/calendar.php?date=<?= e($date) ?>">Tilbage</a>
        </form>
    <?php else: ?>
        <p><a class="btn btn-secondary" href="/calendar.php">Tilbage</a></p>
    <?php endif; ?>
</section>
<?php
layout_end();
