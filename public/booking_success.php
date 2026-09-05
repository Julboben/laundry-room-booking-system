<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/View/layout.php';

use LaundryBooking\Auth\ResidentAccess;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Request;
use LaundryBooking\Http\Response;
use LaundryBooking\Models\Setting;
use LaundryBooking\Services\BookingService;
use LaundryBooking\Services\SettingsService;
use LaundryBooking\Support\DateHelper;

use function LaundryBooking\Support\e;

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

layout_start('Booking oprettet');
?>
<section class="card">
    <h2>Din booking er oprettet.</h2>
    <p>
        <?= e(danish_date_long(DateHelper::fromDateString($booking['booking_date']))) ?>,
        <?= e(substr($booking['start_time'], 0, 5)) ?>&ndash;<?= e(substr($booking['end_time'], 0, 5)) ?>
    </p>
    <p>Navn: <?= e($booking['booking_name']) ?></p>

    <div class="cancellation-code">
        <p>Din aflysningskode:</p>
        <p class="code"><?= e($sessionData['code']) ?></p>
    </div>

    <p class="alert alert-warning">
        Gem denne aflysningskode. Du skal bruge den, hvis du vil aflyse bookingen.
    </p>

    <p><a class="btn btn-primary" href="/calendar.php">Tilbage til kalenderen</a></p>
</section>
<?php
layout_end();
