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
$slots = BookingService::slots();
$lastSlot = end($slots);
$dayHasNoBookableSlots = $lastSlot !== false
    && $now >= DateHelper::fromDateString($today->format('Y-m-d') . ' ' . $lastSlot['start']);
$firstBookableDate = $dayHasNoBookableSlots ? $today->modify('+1 day') : $today;
$latestDate = $today->modify('+' . $settingsService->getBookingWeeksAhead() . ' weeks');
$dateParam = Request::get('date', '') ?? '';
$selectedDate = DateHelper::isValidDateString($dateParam)
    ? DateHelper::fromDateString($dateParam)->setTime(0, 0)
    : $firstBookableDate;

if ($selectedDate < $firstBookableDate) {
    $selectedDate = $firstBookableDate;
} elseif ($selectedDate > $latestDate) {
    $selectedDate = $latestDate;
}

$previousDate = $selectedDate->modify('-1 day');
$nextDate = $selectedDate->modify('+1 day');
$isToday = $selectedDate == $today;
$isFirstDate = $selectedDate == $firstBookableDate;
$isLastDate = $selectedDate == $latestDate;
$dateString = $selectedDate->format('Y-m-d');
$bookings = $bookingService->bookingsForRange($firstBookableDate, $latestDate);
$calendarMessage = $settingsService->getCalendarMessage();
$monthNames = [
    1 => 'januar', 2 => 'februar', 3 => 'marts', 4 => 'april',
    5 => 'maj', 6 => 'juni', 7 => 'juli', 8 => 'august',
    9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december',
];
$dayNames = [
    1 => 'Mandag', 2 => 'Tirsdag', 3 => 'Onsdag', 4 => 'Torsdag',
    5 => 'Fredag', 6 => 'Lørdag', 7 => 'Søndag',
];
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
$availabilityByDate = [];
$availabilityDate = $firstBookableDate;
while ($availabilityDate <= $latestDate) {
    $availabilityDateString = $availabilityDate->format('Y-m-d');
    $availableCount = 0;

    foreach ($slots as $slotKey => $slot) {
        $slotHasStarted = DateHelper::fromDateString($availabilityDateString . ' ' . $slot['start']) <= $now;
        $isBooked = isset($bookings[$availabilityDateString . '|' . $slotKey]);

        if (!$slotHasStarted && !$isBooked) {
            $availableCount++;
        }
    }

    $availabilityByDate[$availabilityDateString] = $availableCount;
    $availabilityDate = $availabilityDate->modify('+1 day');
}

$calendarMonths = [];
$monthCursor = $firstBookableDate->modify('first day of this month');
$lastMonth = $latestDate->modify('first day of this month');
while ($monthCursor <= $lastMonth) {
    $calendarMonths[] = $monthCursor;
    $monthCursor = $monthCursor->modify('+1 month');
}

$weatherForecast = (new WeatherService(__DIR__ . '/../storage/cache'))
    ->getDryingForecast($settingsService->getWeatherPostcode());
$footerImageAvailable = is_file(__DIR__ . '/assets/laundry-footer.png');

layout_start('Kalender', bodyClass: 'page-kiosk page-calendar');
?>
<section class="daily-calendar" aria-labelledby="day-heading">
    <header class="day-toolbar">
        <?php if (!$isFirstDate): ?>
            <a class="day-control" href="/calendar.php?date=<?= e($previousDate->format('Y-m-d')) ?>" aria-label="Forrige dag">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                <span>Forrige</span>
            </a>
        <?php else: ?>
            <span class="day-control is-disabled" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></svg>
                <span>Forrige</span>
            </span>
        <?php endif; ?>

        <div class="day-heading">
            <span class="day-eyebrow"><?= $isToday ? 'I dag' : e($dayNames[(int) $selectedDate->format('N')]) ?></span>
            <h2 id="day-heading"><?= (int) $selectedDate->format('j') ?>. <?= e($monthNames[(int) $selectedDate->format('n')]) ?> <?= e($selectedDate->format('Y')) ?></h2>
            <div class="date-actions">
                <button class="calendar-trigger" type="button" data-calendar-open aria-haspopup="dialog">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z"></path></svg>
                    <span>Vælg en anden dag</span>
                </button>
                <form class="date-picker date-picker-fallback" method="get" action="/calendar.php" data-date-picker>
                    <label class="visually-hidden" for="calendar_date">Vælg en anden dag</label>
                    <input
                        type="date"
                        id="calendar_date"
                        name="date"
                        value="<?= e($dateString) ?>"
                        min="<?= e($firstBookableDate->format('Y-m-d')) ?>"
                        max="<?= e($latestDate->format('Y-m-d')) ?>"
                        aria-label="Vælg en anden dag"
                    >
                    <button class="date-picker-submit" type="submit">Vis</button>
                </form>
                <?php if (!$isToday && $firstBookableDate == $today): ?>
                    <a class="today-shortcut" href="/calendar.php?date=<?= e($today->format('Y-m-d')) ?>">I dag</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isLastDate): ?>
            <a class="day-control day-control-next" href="/calendar.php?date=<?= e($nextDate->format('Y-m-d')) ?>" aria-label="Næste dag">
                <span>Næste</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
            </a>
        <?php else: ?>
            <span class="day-control day-control-next is-disabled" aria-hidden="true">
                <span>Næste</span>
                <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg>
            </span>
        <?php endif; ?>
    </header>

    <dialog class="calendar-dialog" data-calendar-dialog aria-labelledby="calendar-dialog-title">
        <div class="calendar-dialog-header">
            <button type="button" class="month-control" data-month-previous aria-label="Forrige måned">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
            </button>
            <h2 id="calendar-dialog-title" data-month-label>Vælg dag</h2>
            <button type="button" class="month-control" data-month-next aria-label="Næste måned">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
            </button>
            <button type="button" class="dialog-close" data-calendar-close aria-label="Luk kalenderen">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
            </button>
        </div>
        <div class="calendar-weekdays" aria-hidden="true">
            <span>Man</span><span>Tir</span><span>Ons</span><span>Tor</span><span>Fre</span><span>Lør</span><span>Søn</span>
        </div>
        <?php foreach ($calendarMonths as $monthIndex => $calendarMonth): ?>
            <?php
            $monthKey = $calendarMonth->format('Y-m');
            $monthLabel = ucfirst($monthNames[(int) $calendarMonth->format('n')]) . ' ' . $calendarMonth->format('Y');
            $leadingDays = (int) $calendarMonth->format('N') - 1;
            $daysInMonth = (int) $calendarMonth->format('t');
            $isSelectedMonth = $selectedDate->format('Y-m') === $monthKey;
            ?>
            <div
                class="calendar-month<?= $isSelectedMonth ? ' is-active' : '' ?>"
                data-calendar-month
                data-month-label="<?= e($monthLabel) ?>"
                <?= $isSelectedMonth ? '' : 'hidden' ?>
            >
                <?php for ($blank = 0; $blank < $leadingDays; $blank++): ?>
                    <span class="calendar-date is-blank" aria-hidden="true"></span>
                <?php endfor; ?>
                <?php for ($dayNumber = 1; $dayNumber <= $daysInMonth; $dayNumber++): ?>
                    <?php
                    $calendarDate = $calendarMonth->setDate(
                        (int) $calendarMonth->format('Y'),
                        (int) $calendarMonth->format('n'),
                        $dayNumber
                    );
                    $calendarDateString = $calendarDate->format('Y-m-d');
                    $isSelectable = $calendarDate >= $firstBookableDate && $calendarDate <= $latestDate;
                    $availableCount = $availabilityByDate[$calendarDateString] ?? 0;
                    $isSelected = $calendarDateString === $dateString;
                    ?>
                    <?php if ($isSelectable): ?>
                        <a
                            class="calendar-date<?= $isSelected ? ' is-selected' : '' ?><?= $availableCount === 0 ? ' is-full' : '' ?>"
                            href="/calendar.php?date=<?= e($calendarDateString) ?>"
                            aria-label="<?= e(danish_date_long($calendarDate)) ?>, <?= $availableCount ?> ledige tider"
                        >
                            <span class="calendar-date-number"><?= $dayNumber ?></span>
                            <span class="availability-count"><?= $availableCount ?> ledige</span>
                        </a>
                    <?php else: ?>
                        <span class="calendar-date is-outside" aria-hidden="true">
                            <span class="calendar-date-number"><?= $dayNumber ?></span>
                        </span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endforeach; ?>
    </dialog>

    <?php if ($calendarMessage !== ''): ?>
        <p class="calendar-message"><?= e($calendarMessage) ?></p>
    <?php endif; ?>

    <div class="day-slots" role="list" aria-label="Vasketider for <?= e(danish_date_long($selectedDate)) ?>">
        <?php foreach ($slots as $slotKey => $slot): ?>
            <?php
            $booking = $bookings[$dateString . '|' . $slotKey] ?? null;
            $date = $dateString;
            $isPast = DateHelper::fromDateString($dateString . ' ' . $slot['start']) <= $now;
            $slotUrl = $booking !== null
                ? '/cancel.php?id=' . $booking->id . '&date=' . rawurlencode($dateString)
                : '/book.php?date=' . rawurlencode($dateString) . '&slot=' . rawurlencode($slotKey);
            $slotAriaLabel = $booking !== null
                ? sprintf('Aflys bookingen for %s fra %s til %s', $booking->bookingName, $slot['start'], $slot['end'])
                : sprintf('Book tiden fra %s til %s', $slot['start'], $slot['end']);
            ?>
            <?php if ($isPast): ?>
                <article class="day-slot" role="listitem">
            <?php else: ?>
                <a class="day-slot day-slot-clickable" role="listitem" href="<?= e($slotUrl) ?>" aria-label="<?= e($slotAriaLabel) ?>">
            <?php endif; ?>
                <div class="day-slot-time">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                    <span><?= e($slot['start']) ?> – <?= e($slot['end']) ?></span>
                </div>
                <?php include __DIR__ . '/../app/View/partials/slot_card.php'; ?>
            <?php if ($isPast): ?>
                </article>
            <?php else: ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <aside class="resident-note<?= $weatherForecast !== null ? ' weather-note' : '' ?><?= ($weatherForecast['isGoodDryingWeather'] ?? false) ? ' is-good-weather' : '' ?>">
        <div class="note-icon" aria-hidden="true">
            <?php if ($weatherForecast !== null && $weatherForecast['isGoodDryingWeather']): ?>
                <svg class="weather-icon weather-icon-clear" viewBox="0 0 48 48"><circle cx="20" cy="18" r="8"></circle><path d="M20 4v4M20 28v4M6 18h4M30 18h4M10 8l3 3M30 8l-3 3M8 37h21c4 0 4-5 0-5H18M14 42h19c4 0 4-5 0-5"></path></svg>
            <?php elseif ($weatherForecast !== null && $weatherForecast['rainProbability'] > 40): ?>
                <svg class="weather-icon weather-icon-rain" viewBox="0 0 48 48"><path d="M12 31h24a8 8 0 0 0 0-16c-.8 0-1.6.1-2.3.3A12 12 0 0 0 11.2 20 6 6 0 0 0 12 31ZM17 36l-2 5M26 36l-2 5M35 36l-2 5"></path></svg>
            <?php elseif ($weatherForecast !== null): ?>
                <svg class="weather-icon weather-icon-mixed" viewBox="0 0 48 48"><circle cx="17" cy="16" r="7"></circle><path d="M17 4v3M5 16h3M9 8l2 2M27 8l-2 2M13 38h24a8 8 0 0 0 0-16c-.8 0-1.6.1-2.3.3A12 12 0 0 0 12.2 27 6 6 0 0 0 13 38Z"></path></svg>
            <?php else: ?>
                <svg class="tip-icon" viewBox="0 0 48 48"><path d="M17 29c-3-2-5-6-5-10a12 12 0 0 1 24 0c0 4-2 8-5 10-2 2-2 3-2 5H19c0-2 0-3-2-5ZM19 38h10M21 42h6M24 3V0M9 8 6 5M39 8l3-3"></path></svg>
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
