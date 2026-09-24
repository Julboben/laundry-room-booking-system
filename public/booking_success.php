<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/View/layout.php';

use LaundryBooking\Auth\ResidentAccess;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Request;
use LaundryBooking\Http\Response;
use LaundryBooking\Models\Setting;
use LaundryBooking\Services\SettingsService;
use LaundryBooking\Support\DateHelper;

use function LaundryBooking\Support\e;
use function LaundryBooking\Support\t;

const SUCCESS_IDLE_SECONDS = 60;

$pdo = Connection::get();
$settingsService = new SettingsService(new Setting($pdo));
$residentAccess = new ResidentAccess($settingsService);

if (!$residentAccess->isAuthenticated()) {
    Response::redirect('/access.php');
}

$id = (int) (Request::get('id', '0') ?? '0');

$sessionData = $_SESSION['booking_success'] ?? null;
unset($_SESSION['booking_success']);

if ($sessionData === null || (int) $sessionData['id'] !== $id) {
    Response::redirect('/calendar.php');
}

$statement = $pdo->prepare(
    'SELECT booking_date, slot_key, start_time, end_time, booking_name FROM bookings WHERE id = :id'
);
$statement->execute(['id' => $id]);
$booking = $statement->fetch();

if ($booking === false) {
    Response::redirect('/calendar.php');
}

$calendarUrl = '/calendar.php?date=' . rawurlencode($booking['booking_date']);

layout_start('Booking oprettet', bodyClass: 'page-kiosk page-success', bodyData: [
    'idle-return' => $calendarUrl,
    'idle-seconds' => (string) SUCCESS_IDLE_SECONDS,
]);
?>
<section class="card kiosk-card success-card" aria-labelledby="page-heading">
    <?= illustration('clear', 'card-illustration') ?>
    <h1 id="page-heading"><?= e(t('Din tid er booket')) ?></h1>
    <p class="success-details">
        <strong><?= e(ucfirst(danish_date_long(DateHelper::fromDateString($booking['booking_date'])))) ?></strong>
        <span><?= e(t('Kl. %s', clock_range($booking['start_time'], $booking['end_time']))) ?> · <?= e($booking['booking_name']) ?></span>
    </p>

    <div class="cancellation-code">
        <p id="code-label"><?= e(t('Din aflysningskode')) ?></p>
        <p class="code" aria-labelledby="code-label"><?= e($sessionData['code']) ?></p>
        <p class="code-hint"><?= icon('info') ?><span><?= e(t('Tag et billede af koden eller skriv den ned – den bliver ikke vist igen.')) ?></span></p>
    </div>

    <div class="form-actions">
        <a class="btn btn-primary" href="<?= e($calendarUrl) ?>"><?= e(t('Tilbage til kalenderen')) ?></a>
    </div>
    <p class="idle-countdown" data-idle-countdown="<?= e(t('Går tilbage til kalenderen om %d sek.')) ?>" hidden></p>
</section>
<?php
layout_end();
