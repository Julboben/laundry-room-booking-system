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
use LaundryBooking\Support\I18n;

use function LaundryBooking\Support\e;
use function LaundryBooking\Support\t;

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
$displayDate = I18n::locale() === 'en'
    ? I18n::monthName((int) $selectedDate->format('n')) . ' ' . (int) $selectedDate->format('j') . ', ' . $selectedDate->format('Y')
    : (int) $selectedDate->format('j') . '. ' . I18n::monthName((int) $selectedDate->format('n')) . ' ' . $selectedDate->format('Y');
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
            <a class="day-control" href="/calendar.php?date=<?= e($previousDate->format('Y-m-d')) ?>" aria-label="<?= e(t('Forrige dag')) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                <span><?= e(t('Forrige')) ?></span>
            </a>
        <?php else: ?>
            <span class="day-control is-disabled" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></svg>
                <span><?= e(t('Forrige')) ?></span>
            </span>
        <?php endif; ?>

        <div class="day-heading">
            <span class="day-eyebrow"><?= e($isToday ? t('I dag') : I18n::dayName((int) $selectedDate->format('N'))) ?></span>
            <h2 id="day-heading"><?= e($displayDate) ?></h2>
            <div class="date-actions">
                <button class="calendar-trigger" type="button" data-calendar-open aria-haspopup="dialog">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z"></path></svg>
                    <span><?= e(t('Vælg en anden dag')) ?></span>
                </button>
                <form class="date-picker date-picker-fallback" method="get" action="/calendar.php" data-date-picker>
                    <label class="visually-hidden" for="calendar_date"><?= e(t('Vælg en anden dag')) ?></label>
                    <input
                        type="date"
                        id="calendar_date"
                        name="date"
                        value="<?= e($dateString) ?>"
                        min="<?= e($firstBookableDate->format('Y-m-d')) ?>"
                        max="<?= e($latestDate->format('Y-m-d')) ?>"
                        aria-label="<?= e(t('Vælg en anden dag')) ?>"
                    >
                    <button class="date-picker-submit" type="submit"><?= e(t('Vis')) ?></button>
                </form>
                <?php if (!$isToday && $firstBookableDate == $today): ?>
                    <a class="today-shortcut" href="/calendar.php?date=<?= e($today->format('Y-m-d')) ?>"><?= e(t('I dag')) ?></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isLastDate): ?>
            <a class="day-control day-control-next" href="/calendar.php?date=<?= e($nextDate->format('Y-m-d')) ?>" aria-label="<?= e(t('Næste dag')) ?>">
                <span><?= e(t('Næste')) ?></span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
            </a>
        <?php else: ?>
            <span class="day-control day-control-next is-disabled" aria-hidden="true">
                <span><?= e(t('Næste')) ?></span>
                <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg>
            </span>
        <?php endif; ?>
    </header>

    <dialog class="calendar-dialog" data-calendar-dialog aria-labelledby="calendar-dialog-title">
        <div class="calendar-dialog-header">
            <button type="button" class="month-control" data-month-previous aria-label="<?= e(t('Forrige måned')) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
            </button>
            <h2 id="calendar-dialog-title" data-month-label><?= e(t('Vælg dag')) ?></h2>
            <button type="button" class="month-control" data-month-next aria-label="<?= e(t('Næste måned')) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
            </button>
            <button type="button" class="dialog-close" data-calendar-close aria-label="<?= e(t('Luk kalenderen')) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
            </button>
        </div>
        <div class="calendar-weekdays" aria-hidden="true">
            <span><?= e(t('Man')) ?></span><span><?= e(t('Tir')) ?></span><span><?= e(t('Ons')) ?></span><span><?= e(t('Tor')) ?></span><span><?= e(t('Fre')) ?></span><span><?= e(t('Lør')) ?></span><span><?= e(t('Søn')) ?></span>
        </div>
        <?php foreach ($calendarMonths as $monthIndex => $calendarMonth): ?>
            <?php
            $monthKey = $calendarMonth->format('Y-m');
            $monthLabel = I18n::monthName((int) $calendarMonth->format('n')) . ' ' . $calendarMonth->format('Y');
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
                            aria-label="<?= e(t('%s, %d ledige tider', danish_date_long($calendarDate), $availableCount)) ?>"
                        >
                            <span class="calendar-date-number"><?= $dayNumber ?></span>
                            <span class="availability-count"><?= e(t('%d ledige', $availableCount)) ?></span>
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

    <div class="day-slots" role="list" aria-label="<?= e(t('Vasketider for %s', danish_date_long($selectedDate))) ?>">
        <?php foreach ($slots as $slotKey => $slot): ?>
            <?php
            $booking = $bookings[$dateString . '|' . $slotKey] ?? null;
            $date = $dateString;
            $isPast = DateHelper::fromDateString($dateString . ' ' . $slot['start']) <= $now;
            $slotUrl = $booking !== null
                ? '/cancel.php?id=' . $booking->id . '&date=' . rawurlencode($dateString)
                : '/book.php?date=' . rawurlencode($dateString) . '&slot=' . rawurlencode($slotKey);
            $slotAriaLabel = $booking !== null
                ? t('Aflys bookingen for %s fra %s til %s', $booking->bookingName, $slot['start'], $slot['end'])
                : t('Book tiden fra %s til %s', $slot['start'], $slot['end']);
            ?>
            <?php
            $slotStateClass = $isPast ? 'day-slot-past' : ($booking !== null ? 'day-slot-booked' : 'day-slot-available');
            ?>
            <?php if ($isPast): ?>
                <article class="day-slot <?= e($slotStateClass) ?>" role="listitem">
            <?php else: ?>
                <a class="day-slot day-slot-clickable <?= e($slotStateClass) ?>" role="listitem" href="<?= e($slotUrl) ?>" aria-label="<?= e($slotAriaLabel) ?>">
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
                <h2><?= e($weatherForecast['isGoodDryingWeather'] ? t('Godt tørrevejr') : t('Tørrevejret')) ?> <?= e($weatherForecast['period']) ?></h2>
                <p><?= e($weatherForecast['recommendation']) ?></p>
                <div class="weather-meta">
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3s5 6 5 11a5 5 0 0 1-10 0c0-5 5-11 5-11Z"></path></svg><?= e($weatherForecast['location']) ?></span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"></path></svg><?= (int) $weatherForecast['temperature'] ?>&deg;C</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17a4 4 0 1 1 1-7.9A6 6 0 0 1 19 12a3 3 0 0 1-1 5H7ZM9 20l-1 2M14 20l-1 2"></path></svg><?= e(t('%d%% regn', (int) $weatherForecast['rainProbability'])) ?></span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 8h10c3 0 3-4 0-4M3 12h15c4 0 4 5 0 5M3 16h8"></path></svg><?= e(t('%d km/t vind', (int) $weatherForecast['windSpeed'])) ?></span>
                    <span class="weather-source"><?= e(t('Vejrdata fra Open-Meteo')) ?></span>
                </div>
            <?php else: ?>
                <h2><?= e(t('Dagens vasketip')) ?></h2>
                <p><?= e(t($laundryTip)) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($footerImageAvailable): ?>
            <img class="note-illustration" src="/assets/laundry-footer.png" alt="" aria-hidden="true">
        <?php endif; ?>
    </aside>
</section>
<?php
layout_end();
