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
$now = DateHelper::now();
$weekParam = Request::get('week');
$weekOffset = 0;

$maxWeekOffset = $settingsService->getBookingWeeksAhead();

if ($weekParam !== null && preg_match('/^-?\d+$/', $weekParam)) {
    $weekOffset = max(0, min($maxWeekOffset, (int) $weekParam));
}

$weekStart = DateHelper::startOfWeek($today)->modify(sprintf('%+d weeks', $weekOffset));

$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = $weekStart->modify("+{$i} days");
}

$weekEnd = $days[6];
$weekNumber = (int) $weekStart->format('W');
$monthNames = [
    1 => 'januar', 2 => 'februar', 3 => 'marts', 4 => 'april',
    5 => 'maj', 6 => 'juni', 7 => 'juli', 8 => 'august',
    9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december',
];
$rangeStartMonth = $monthNames[(int) $weekStart->format('n')];
$rangeEndMonth = $monthNames[(int) $weekEnd->format('n')];
$weekRange = $weekStart->format('n') === $weekEnd->format('n')
    ? sprintf('%d. – %d. %s %s', (int) $weekStart->format('j'), (int) $weekEnd->format('j'), $rangeEndMonth, $weekEnd->format('Y'))
    : sprintf('%d. %s – %d. %s %s', (int) $weekStart->format('j'), $rangeStartMonth, (int) $weekEnd->format('j'), $rangeEndMonth, $weekEnd->format('Y'));
$bookings = $bookingService->bookingsForRange($weekStart, $weekEnd);
$slots = BookingService::slots();
$takeoverMinutes = $settingsService->getTakeoverRuleMinutes();
$calendarMessage = $settingsService->getCalendarMessage();

layout_start('Kalender');
?>
<section class="calendar-page" aria-labelledby="week-heading">
    <div class="week-toolbar">
        <div class="week-actions">
            <?php if ($weekOffset > 0): ?>
                <a class="week-control week-previous" href="/calendar.php?week=<?= $weekOffset - 1 ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                    <span>Forrige uge</span>
                </a>
                <a class="week-control week-today" href="/calendar.php?week=0">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z"></path><path d="M8 13h3v3H8z"></path></svg>
                    <span>I dag</span>
                </a>
            <?php endif; ?>
        </div>
        <div class="week-title">
            <h2 id="week-heading">Uge <?= $weekNumber ?></h2>
            <p><?= e($weekRange) ?></p>
        </div>
        <?php if ($weekOffset < $maxWeekOffset): ?>
            <a class="week-control week-next" href="/calendar.php?week=<?= $weekOffset + 1 ?>">
                <span>Næste uge</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
            </a>
        <?php endif; ?>
    </div>

    <div class="takeover-notice">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v6M12 7.5v.5"></path></svg>
        <p>Hvis en booket tid ikke er taget i brug senest <?= (int) $takeoverMinutes ?> minutter efter starttidspunktet, må en anden beboer overtage tiden manuelt.</p>
    </div>
    <?php if ($calendarMessage !== ''): ?>
        <p class="calendar-message"><?= e($calendarMessage) ?></p>
    <?php endif; ?>

    <p class="calendar-swipe-hint" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="m9 18-6-6 6-6M15 6l6 6-6 6"></path></svg>
        Stryg for at se hele ugen
    </p>
    <div class="calendar-scroll" tabindex="0" aria-label="Ugens ledige og bookede vasketider">
        <div class="calendar-grid" role="table" aria-label="Vaskekalender for uge <?= $weekNumber ?>">
            <div class="calendar-row" role="row">
                <div class="calendar-corner" role="columnheader"><span>Tid</span></div>
                <?php foreach ($days as $day): ?>
                    <?php
                    $isToday = $day->format('Y-m-d') === $today->format('Y-m-d');
                    $isPastDay = $day < $today;
                    ?>
                    <div class="calendar-day-heading<?= $isToday ? ' is-today' : '' ?><?= $isPastDay ? ' is-past' : '' ?>" role="columnheader">
                        <span class="day-name"><?= e(ucfirst(explode(' ', danish_date_short($day))[0])) ?></span>
                        <span class="day-date"><?= e($day->format('j/n')) ?></span>
                        <?php if ($isToday): ?><span class="today-label">I dag</span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php $rowIndex = 0; ?>
            <?php foreach ($slots as $slotKey => $slot): ?>
                <div class="calendar-row" role="row">
                    <div class="calendar-time" role="rowheader" style="--delay: <?= 110 + ($rowIndex * 70) ?>ms">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                        <span><?= e($slot['start']) ?> – <?= e($slot['end']) ?></span>
                    </div>
                    <?php foreach ($days as $day): ?>
                        <?php
                        $dateString = $day->format('Y-m-d');
                        $booking = $bookings[$dateString . '|' . $slotKey] ?? null;
                        $date = $dateString;
                        $isPast = DateHelper::fromDateString($dateString . ' ' . $slot['start']) <= $now;
                        ?>
                        <div class="calendar-cell" role="cell" style="--delay: <?= 110 + ($rowIndex * 70) ?>ms">
                            <?php include __DIR__ . '/../app/View/partials/slot_card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php $rowIndex++; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <aside class="resident-note">
        <div class="note-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48"><path d="M24 41S8 32 8 19a9 9 0 0 1 16-6 9 9 0 0 1 16 6c0 13-16 22-16 22Z"></path><path d="m18 23 4 4 8-9"></path></svg>
        </div>
        <div class="note-copy">
            <h2>Vigtigt at vide</h2>
            <p>Skriv kun dit fornavn eller et navn, du er indforstået med, at andre beboere kan se.</p>
            <p>Når du booker en tid, får du en aflysningskode. Gem den!</p>
        </div>
        <div class="note-illustration" aria-hidden="true">
            <span class="towel-stack"></span>
            <svg viewBox="0 0 120 100"><path d="M61 82V34M61 48C50 48 43 40 42 27c12 0 19 8 19 21ZM61 60c12 0 20-8 22-21-13-1-21 7-22 21ZM61 36c9-4 13-13 10-24-11 4-15 13-10 24ZM48 68c-10 0-18-6-21-17 11-2 19 5 21 17ZM72 70c10 0 17-6 20-17-11-1-19 5-20 17Z"></path><path d="M38 76h47l-5 20H43z"></path></svg>
        </div>
    </aside>
</section>
<?php
layout_end();
