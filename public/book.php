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
use function LaundryBooking\Support\t;

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
$slotBlocked = false;
$name = '';

if (Request::isPost()) {
    $date = Request::post('date', '') ?? '';
    $slotKey = Request::post('slot', '') ?? '';
    $name = Request::post('booking_name', '') ?? '';

    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = t('Sikkerhedstjekket mislykkedes. Prøv igen.');
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

        $error = t($result->error);
    }
} else {
    $dateSlotError = $bookingService->validateDateAndSlot($date, $slotKey);
    if ($dateSlotError !== null) {
        $error = t($dateSlotError);
    } elseif (!$bookingService->isAvailable($date, $slotKey)) {
        $error = t('Tiden er desværre allerede booket.');
    }
    $slotBlocked = $error !== null;
}

$slots = BookingService::slots();
$slot = $slots[$slotKey] ?? null;
$hasValidSlot = $slot !== null && DateHelper::isValidDateString($date) && !$slotBlocked;
$takeoverMinutes = $settingsService->getTakeoverRuleMinutes();
$hasStarted = $hasValidSlot && DateHelper::fromDateString($date . ' ' . $slot['start']) <= DateHelper::now();
$calendarUrl = DateHelper::isValidDateString($date) ? '/calendar.php?date=' . rawurlencode($date) : '/calendar.php';

layout_start('Bekræft booking', bodyClass: 'page-kiosk page-booking', bodyData: ['idle-return' => $calendarUrl]);
?>
<section class="card kiosk-card booking-card" aria-labelledby="page-heading">
    <h1 id="page-heading"><?= e(t('Bekræft din tid')) ?></h1>
    <?php if ($error !== null): ?>
        <p class="alert alert-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($hasValidSlot): ?>
        <div class="booking-summary">
            <span class="booking-summary-icon" aria-hidden="true"><?= icon('calendar-day') ?></span>
            <div>
                <strong><?= e(ucfirst(danish_date_long(DateHelper::fromDateString($date)))) ?></strong>
                <span><?= e(t('Kl. %s', clock_range($slot['start'], $slot['end']))) ?></span>
            </div>
        </div>
        <?php if ($hasStarted && $error === null): ?>
            <p class="alert alert-info"><?= e(t('Tiden er allerede i gang – du har maskinerne til kl. %s.', clock_time($slot['end']))) ?></p>
        <?php endif; ?>
        <form method="post" action="/book.php" novalidate>
            <?= Csrf::field() ?>
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <input type="hidden" name="slot" value="<?= e($slotKey) ?>">

            <label for="booking_name"><?= e(t('Dit navn')) ?></label>
            <input
                type="text"
                id="booking_name"
                name="booking_name"
                value="<?= e($name) ?>"
                minlength="2"
                maxlength="100"
                autocomplete="off"
                autocapitalize="words"
                spellcheck="false"
                enterkeyhint="done"
                aria-describedby="name-help"
                required
            >
            <small class="form-hint" id="name-help"><?= e(t('Navnet vises i kalenderen, så andre kan se, hvem der har tiden. Fornavn og lejlighed er nok.')) ?></small>

            <div class="booking-notes">
                <h2><?= e(t('Godt at vide')) ?></h2>
                <ul>
                    <li><?= icon('clock') ?><span><?= e($hasStarted
                        ? t('Er du ikke i gang senest %d minutter efter, at du har booket, må en anden beboer overtage tiden.', $takeoverMinutes)
                        : t('Er du ikke i gang senest %d minutter efter starttid, må en anden beboer overtage tiden.', $takeoverMinutes)) ?></span></li>
                    <li><?= icon('key') ?><span><?= e(t('Når du har booket, får du en aflysningskode. Gem den, så du kan aflyse senere.')) ?></span></li>
                </ul>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= e(t('Bekræft booking')) ?></button>
                <a class="btn btn-secondary" href="<?= e($calendarUrl) ?>"><?= e(t('Tilbage')) ?></a>
            </div>
        </form>
    <?php else: ?>
        <div class="form-actions">
            <a class="btn btn-primary" href="<?= e($calendarUrl) ?>"><?= e(t('Tilbage til kalenderen')) ?></a>
        </div>
    <?php endif; ?>
</section>
<?php
layout_end();
