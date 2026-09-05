<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/View/layout.php';

use LaundryBooking\Auth\ResidentAccess;
use LaundryBooking\Database\Connection;
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

$today = DateHelper::today();
$weekParam = Request::get('week');
$weekOffset = 0;

if ($weekParam !== null && preg_match('/^-?\d+$/', $weekParam)) {
    $weekOffset = (int) $weekParam;
}

$weekStart = DateHelper::startOfWeek($today)->modify(sprintf('%+d weeks', $weekOffset));

$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = $weekStart->modify("+{$i} days");
}

$weekEnd = $days[6];
$bookings = $bookingService->bookingsForRange($weekStart, $weekEnd);
$slots = BookingService::slots();
$takeoverMinutes = $settingsService->getTakeoverRuleMinutes();
$calendarMessage = $settingsService->getCalendarMessage();

layout_start('Kalender');
?>
<section class="card">
    <nav class="week-nav">
        <a class="btn btn-secondary" href="/calendar.php?week=<?= $weekOffset - 1 ?>">&laquo; Forrige uge</a>
        <a class="btn btn-secondary" href="/calendar.php?week=0">I dag</a>
        <a class="btn btn-secondary" href="/calendar.php?week=<?= $weekOffset + 1 ?>">Næste uge &raquo;</a>
    </nav>
    <p class="takeover-notice">
        Hvis en booket tid ikke er taget i brug senest <?= (int) $takeoverMinutes ?> minutter efter starttidspunktet,
        må en anden beboer overtage tiden manuelt.
    </p>
    <?php if ($calendarMessage !== ''): ?>
        <p class="calendar-message"><?= e($calendarMessage) ?></p>
    <?php endif; ?>

    <div class="calendar-grid">
        <?php foreach ($days as $day): ?>
            <?php $dateString = $day->format('Y-m-d'); ?>
            <div class="calendar-day">
                <h3><?= e(danish_date_long($day)) ?></h3>
                <div class="slot-list">
                    <?php foreach ($slots as $slotKey => $slot): ?>
                        <?php
                        $booking = $bookings[$dateString . '|' . $slotKey] ?? null;
                        $date = $dateString;
                        include __DIR__ . '/../app/View/partials/slot_card.php';
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php
layout_end();
