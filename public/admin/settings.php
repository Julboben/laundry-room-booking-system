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
        $error = 'Sikkerhedstjek fejlede. Prøv igen.';
    } else {
        $weeksAhead = (int) (Request::post('booking_weeks_ahead', '8') ?? '8');
        $calendarMessage = Request::post('calendar_message', '') ?? '';
        $newPropertyCode = trim(Request::post('new_property_code', '') ?? '');

        if ($weeksAhead < 1 || $weeksAhead > 52) {
            $error = 'Antal uger skal være mellem 1 og 52.';
        } elseif ($newPropertyCode !== '' && mb_strlen($newPropertyCode) < 4) {
            $error = 'Den nye ejendomskode skal være mindst 4 tegn.';
        } else {
            $settingsService->setBookingWeeksAhead($weeksAhead);
            $settingsService->setCalendarMessage($calendarMessage);

            $activityLog->log(
                actorType: 'admin',
                action: 'settings_updated',
                ipAddress: Request::ip(),
                userAgent: Request::userAgent(),
                details: 'booking_weeks_ahead=' . $weeksAhead
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

            $success = 'Indstillingerne er gemt.';
        }
    }
}

$weeksAhead = $settingsService->getBookingWeeksAhead();
$calendarMessage = $settingsService->getCalendarMessage();

layout_start('Indstillinger');
?>
<section class="card">
    <h2>Indstillinger</h2>
    <p><a href="/admin/index.php">&laquo; Tilbage til administration</a></p>

    <?php if ($error !== null): ?>
        <p class="alert alert-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <?php if ($success !== null): ?>
        <p class="alert alert-success" role="status"><?= e($success) ?></p>
    <?php endif; ?>

    <form method="post" action="/admin/settings.php" novalidate>
        <?= Csrf::field() ?>

        <label for="booking_weeks_ahead">Antal uger frem, der kan bookes</label>
        <input
            type="number"
            id="booking_weeks_ahead"
            name="booking_weeks_ahead"
            min="1"
            max="52"
            value="<?= (int) $weeksAhead ?>"
            required
        >

        <label for="calendar_message">Besked i kalenderen (valgfri)</label>
        <textarea id="calendar_message" name="calendar_message" maxlength="500"><?= e($calendarMessage) ?></textarea>

        <label for="new_property_code">Ny ejendomskode (lad stå tom for ikke at ændre)</label>
        <input type="text" id="new_property_code" name="new_property_code" autocomplete="off">

        <button type="submit" class="btn btn-primary">Gem</button>
    </form>
</section>
<?php
layout_end();
