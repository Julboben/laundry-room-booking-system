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
use LaundryBooking\Models\Setting;
use LaundryBooking\Services\SettingsService;

use function LaundryBooking\Support\e;
use function LaundryBooking\Support\t;

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
$settingsService = new SettingsService(new Setting($pdo));
$activityLog = new ActivityLog($pdo);

$error = null;
$success = null;

if (Request::isPost()) {
    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = t('Sikkerhedstjekket mislykkedes. Prøv igen.');
    } else {
        $weeksAhead = (int) (Request::post('booking_weeks_ahead', '8') ?? '8');
        $calendarMessage = Request::post('calendar_message', '') ?? '';
        $weatherPostcode = trim(Request::post('weather_postcode', '') ?? '');
        $cancellationCodeLength = (int) (Request::post('cancellation_code_length', '4') ?? '4');
        $lateBookingMinutesInput = trim(Request::post('late_booking_minutes', '') ?? '');
        $lateBookingMinutes = (int) $lateBookingMinutesInput;
        $newPropertyCode = trim(Request::post('new_property_code', '') ?? '');

        if ($weeksAhead < 1 || $weeksAhead > 52) {
            $error = t('Antal uger skal være mellem 1 og 52.');
        } elseif (preg_match('/^\d{4}$/', $weatherPostcode) !== 1) {
            $error = t('Postnummeret skal bestå af 4 cifre.');
        } elseif ($cancellationCodeLength < 4 || $cancellationCodeLength > 8) {
            $error = t('Aflysningskoden skal være mellem 4 og 8 cifre.');
        } elseif (
            preg_match('/^\d{1,3}$/', $lateBookingMinutesInput) !== 1
            || $lateBookingMinutes > SettingsService::MAX_LATE_BOOKING_MINUTES
        ) {
            $error = t('Minutter efter starttid skal være mellem 0 og %d.', SettingsService::MAX_LATE_BOOKING_MINUTES);
        } elseif ($newPropertyCode !== '' && preg_match('/^\d{4,8}$/', $newPropertyCode) !== 1) {
            $error = t('Den nye ejendomskode skal bestå af 4-8 cifre.');
        } else {
            $pdo->beginTransaction();

            try {
                $settingsService->setBookingWeeksAhead($weeksAhead);
                $settingsService->setCalendarMessage($calendarMessage);
                $settingsService->setWeatherPostcode($weatherPostcode);
                $settingsService->setCancellationCodeLength($cancellationCodeLength);
                $settingsService->setLateBookingMinutes($lateBookingMinutes);
                $activityLog->log(
                    actorType: 'admin',
                    action: 'settings_updated',
                    ipAddress: Request::ip(),
                    userAgent: Request::userAgent(),
                    details: 'booking_weeks_ahead=' . $weeksAhead
                        . ', weather_postcode=' . $weatherPostcode
                        . ', cancellation_code_length=' . $cancellationCodeLength
                        . ', late_booking_minutes=' . $lateBookingMinutes
                );

                if ($newPropertyCode !== '') {
                    $settingsService->setPropertyCodeHash(password_hash($newPropertyCode, PASSWORD_DEFAULT));

                    $activityLog->log(
                        actorType: 'admin',
                        action: 'property_code_changed',
                        ipAddress: Request::ip(),
                        userAgent: Request::userAgent(),
                    );
                }

                $pdo->commit();
                $success = t('Indstillingerne er gemt.');
            } catch (\Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $exception;
            }
        }
    }
}

$weeksAhead = $settingsService->getBookingWeeksAhead();
$calendarMessage = $settingsService->getCalendarMessage();
$weatherPostcode = $settingsService->getWeatherPostcode();
$cancellationCodeLength = $settingsService->getCancellationCodeLength();
$lateBookingMinutes = $settingsService->getLateBookingMinutes();

layout_start('Indstillinger');
?>
<section class="card">
    <h1><?= e(t('Indstillinger')) ?></h1>
    <a class="back-link" href="/admin/index.php"><?= icon('chevron-left') ?><?= e(t('Tilbage til administration')) ?></a>

    <?php if ($error !== null): ?>
        <p class="alert alert-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <?php if ($success !== null): ?>
        <p class="alert alert-success" role="status"><?= e($success) ?></p>
    <?php endif; ?>

    <form method="post" action="/admin/settings.php" novalidate>
        <?= Csrf::field() ?>

        <label for="booking_weeks_ahead"><?= e(t('Hvor mange uger frem kan der bookes?')) ?></label>
        <input
            type="number"
            id="booking_weeks_ahead"
            name="booking_weeks_ahead"
            min="1"
            max="52"
            value="<?= (int) $weeksAhead ?>"
            required
        >

        <label for="late_booking_minutes"><?= e(t('Hvor længe efter starttid kan en ledig tid bookes? (minutter)')) ?></label>
        <input
            type="number"
            id="late_booking_minutes"
            name="late_booking_minutes"
            min="0"
            max="<?= SettingsService::MAX_LATE_BOOKING_MINUTES ?>"
            step="5"
            value="<?= (int) $lateBookingMinutes ?>"
            aria-describedby="late_booking_minutes_help"
            required
        >
        <small class="form-hint" id="late_booking_minutes_help"><?= e(t('0 betyder, at en tid kun kan bookes før starttid. Med 120 kan tiden 07.00–10.00 bookes indtil kl. 09.00.')) ?></small>

        <label for="calendar_message"><?= e(t('Besked i kalenderen (valgfri)')) ?></label>
        <textarea id="calendar_message" name="calendar_message" maxlength="500"><?= e($calendarMessage) ?></textarea>

        <label for="weather_postcode"><?= e(t('Postnummer til tørrevejr')) ?></label>
        <input
            type="text"
            id="weather_postcode"
            name="weather_postcode"
            value="<?= e($weatherPostcode) ?>"
            inputmode="numeric"
            pattern="\d{4}"
            maxlength="4"
            aria-describedby="weather_postcode_help"
            required
        >
        <small class="form-hint" id="weather_postcode_help"><?= e(t('Bruges til vejrudsigten i kalenderen. Standard er 1352 København K.')) ?></small>

        <label for="cancellation_code_length"><?= e(t('Antal cifre i aflysningskoden')) ?></label>
        <input
            type="number"
            id="cancellation_code_length"
            name="cancellation_code_length"
            value="<?= (int) $cancellationCodeLength ?>"
            min="4"
            max="8"
            aria-describedby="cancellation_code_length_help"
            required
        >
        <small class="form-hint" id="cancellation_code_length_help"><?= e(t('Mellem 4 og 8 cifre. En ændring gælder straks, så brug helst den samme længde under aktive bookinger.')) ?></small>

        <label for="new_property_code"><?= e(t('Ny ejendomskode (lad feltet stå tomt for at beholde den nuværende)')) ?></label>
        <input
            type="text"
            id="new_property_code"
            name="new_property_code"
            inputmode="numeric"
            pattern="\d{4,8}"
            maxlength="8"
            autocomplete="off"
            aria-describedby="new_property_code_help"
        >
        <small class="form-hint" id="new_property_code_help"><?= e(t('4-8 cifre. Beboerne taster koden på iPad’ens taltastatur.')) ?></small>

        <button type="submit" class="btn btn-primary"><?= e(t('Gem')) ?></button>
    </form>
</section>
<?php
layout_end();
