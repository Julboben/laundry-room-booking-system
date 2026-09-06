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
use LaundryBooking\Services\WeatherService;
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
$calendarMessage = $settingsService->getCalendarMessage();
$laundryTips = [
    'Ryst tøjet godt, inden du hænger det op. Det giver færre folder og kortere tørretid.',
    'Lad lågen til vaskemaskinen stå på klem efter brug, så maskinen kan tørre og holde sig frisk.',
    'Et ekstra centrifugeringsprogram kan forkorte tørretiden for håndklæder og sengetøj.',
    'Fyld tromlen uden at presse tøjet sammen – cirka en håndsbredde fri plads er en god tommelfingerregel.',
    'Vend mørkt tøj på vrangen før vask. Det hjælper farven med at holde sig pæn længere.',
    'Sortér efter både farve og materiale. Tunge håndklæder og let tøj tørrer bedst hver for sig.',
    'Tør gerne tøjet udenfor, når vejret tillader det – frisk luft er både gratis og skånsom.',
];
$laundryTip = $laundryTips[(int) $today->format('z') % count($laundryTips)];
$weatherPostcode = $settingsService->getWeatherPostcode();
$weatherForecast = (new WeatherService(__DIR__ . '/../storage/cache'))->getDryingForecast($weatherPostcode);
$footerImageAvailable = is_file(__DIR__ . '/assets/laundry-footer.png');

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

    <aside class="resident-note<?= $weatherForecast !== null ? ' weather-note' : '' ?><?= ($weatherForecast['isGoodDryingWeather'] ?? false) ? ' is-good-weather' : '' ?>">
        <div class="note-icon" aria-hidden="true">
            <?php if ($weatherForecast !== null && $weatherForecast['isGoodDryingWeather']): ?>
                <svg class="weather-icon weather-icon-clear" viewBox="0 0 48 48">
                    <circle cx="20" cy="18" r="8"></circle>
                    <path d="M20 4v4M20 28v4M6 18h4M30 18h4M10 8l3 3M30 8l-3 3"></path>
                    <path d="M8 37h21c4 0 4-5 0-5H18M14 42h19c4 0 4-5 0-5"></path>
                </svg>
            <?php elseif ($weatherForecast !== null && $weatherForecast['rainProbability'] > 40): ?>
                <svg class="weather-icon weather-icon-rain" viewBox="0 0 48 48">
                    <path d="M12 31h24a8 8 0 0 0 0-16c-.8 0-1.6.1-2.3.3A12 12 0 0 0 11.2 20 6 6 0 0 0 12 31Z"></path>
                    <path d="m17 36-2 5M26 36l-2 5M35 36l-2 5"></path>
                </svg>
            <?php elseif ($weatherForecast !== null): ?>
                <svg class="weather-icon weather-icon-mixed" viewBox="0 0 48 48">
                    <circle cx="17" cy="16" r="7"></circle>
                    <path d="M17 4v3M5 16h3M9 8l2 2M27 8l-2 2"></path>
                    <path d="M13 38h24a8 8 0 0 0 0-16c-.8 0-1.6.1-2.3.3A12 12 0 0 0 12.2 27 6 6 0 0 0 13 38Z"></path>
                </svg>
            <?php else: ?>
                <svg class="tip-icon" viewBox="0 0 48 48"><path d="M17 29c-3-2-5-6-5-10a12 12 0 0 1 24 0c0 4-2 8-5 10-2 2-2 3-2 5H19c0-2 0-3-2-5Z"></path><path d="M19 38h10M21 42h6M24 3V0M9 8 6 5M39 8l3-3"></path></svg>
            <?php endif; ?>
        </div>
        <div class="note-copy">
            <?php if ($weatherForecast !== null): ?>
                <h2><?= $weatherForecast['isGoodDryingWeather'] ? 'Godt tørrevejr' : 'Tørrevejret' ?> <?= e($weatherForecast['period']) ?></h2>
                <p><?= e($weatherForecast['recommendation']) ?></p>
                <div class="weather-meta">
                    <span><?= e($weatherForecast['location']) ?></span>
                    <span><?= (int) $weatherForecast['temperature'] ?>&deg;C</span>
                    <span><?= (int) $weatherForecast['rainProbability'] ?>% regn</span>
                    <span><?= (int) $weatherForecast['windSpeed'] ?> km/t vind</span>
                    <span>Vejrdata fra Open-Meteo</span>
                </div>
            <?php else: ?>
                <h2>Dagens vasketip</h2>
                <p><?= e($laundryTip) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($footerImageAvailable): ?>
            <img class="note-illustration" src="/assets/laundry-footer.png" alt="" aria-hidden="true">
        <?php endif; ?>
    </aside>
</section>
<?php
layout_end();
