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
use LaundryBooking\Services\SlotStatus;
use LaundryBooking\Services\WeatherService;
use LaundryBooking\Support\DateHelper;
use LaundryBooking\Support\I18n;

use function LaundryBooking\Support\e;
use function LaundryBooking\Support\t;
use function LaundryBooking\Support\tn;

$pdo = Connection::get();
$settingModel = new Setting($pdo);
$settingsService = new SettingsService($settingModel);
$residentAccess = new ResidentAccess($settingsService);

if (!$residentAccess->isAuthenticated()) {
    Response::redirect('/access.php');
}

$bookingService = new BookingService($pdo, new CodeService(), $settingsService, new ActivityLog($pdo));
$today = DateHelper::today();
$now = DateHelper::now();
$slots = BookingService::slots();
$lateBookingMinutes = $settingsService->getLateBookingMinutes();
$takeoverMinutes = $settingsService->getTakeoverRuleMinutes();
$lastSlotKey = array_key_last($slots);
$todayIsOver = $now >= $bookingService->bookingDeadline($today->format('Y-m-d'), $lastSlotKey);
$firstBookableDate = $todayIsOver ? $today->modify('+1 day') : $today;
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

$dateString = $selectedDate->format('Y-m-d');
$previousDate = $selectedDate > $firstBookableDate ? $selectedDate->modify('-1 day') : null;
$nextDate = $selectedDate < $latestDate ? $selectedDate->modify('+1 day') : null;
$isToday = $selectedDate == $today;
$bookings = $bookingService->bookingsForRange($firstBookableDate, $latestDate);
$calendarMessage = $settingsService->getCalendarMessage();

$slotStatus = static function (string $date, string $slotKey) use ($slots, $bookings, $now, $lateBookingMinutes): SlotStatus {
    return BookingService::statusFor($date, $slots[$slotKey], isset($bookings[$date . '|' . $slotKey]), $now, $lateBookingMinutes);
};
$calendarUrl = static fn (DateTimeImmutable $date): string => '/calendar.php?date=' . $date->format('Y-m-d');

/** @var array<string,list<bool>> $bookableByDate one flag per slot */
$bookableByDate = [];
for ($cursor = $firstBookableDate; $cursor <= $latestDate; $cursor = $cursor->modify('+1 day')) {
    $cursorString = $cursor->format('Y-m-d');
    $bookableByDate[$cursorString] = array_map(
        static fn (string $slotKey): bool => $slotStatus($cursorString, $slotKey)->isBookable(),
        array_keys($slots)
    );
}

$availabilityLabel = static function (string $date) use ($bookableByDate): string {
    $count = count(array_filter($bookableByDate[$date] ?? []));

    return $count === 0 ? t('Fuldt booket') : tn($count, '%d ledig tid', '%d ledige tider');
};

$dayTitle = static function (DateTimeImmutable $date) use ($today): string {
    $day = I18n::dayName((int) $date->format('N'));
    $month = I18n::monthName((int) $date->format('n'));
    $year = $date->format('Y') !== $today->format('Y') ? ' ' . $date->format('Y') : '';

    return I18n::locale() === 'en'
        ? sprintf('%s, %s %d%s', $day, $month, (int) $date->format('j'), $year !== '' ? ',' . $year : '')
        : sprintf('%s %d. %s%s', mb_convert_case($day, MB_CASE_TITLE), (int) $date->format('j'), $month, $year);
};

$daysFromToday = (int) $today->diff($selectedDate)->days;
$dayEyebrow = match (true) {
    $daysFromToday === 0 => t('I dag'),
    $daysFromToday === 1 => t('I morgen'),
    default => tn($daysFromToday, 'Om %d dag', 'Om %d dage'),
};

$weekdayShort = ['Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør', 'Søn'];
$stripStart = $selectedDate->modify('-' . ((int) $selectedDate->format('N') - 1) . ' days');
$stripEnd = $stripStart->modify('+6 days');

$calendarMonths = [];
for ($monthCursor = $firstBookableDate->modify('first day of this month'); $monthCursor <= $latestDate; $monthCursor = $monthCursor->modify('+1 month')) {
    $calendarMonths[] = $monthCursor;
}

$refreshCandidates = [$today->modify('+1 day')];
foreach ($slots as $slotKey => $slot) {
    $slotStart = DateHelper::fromDateString($dateString . ' ' . $slot['start']);
    $refreshCandidates[] = $slotStart;
    $slotBooking = $bookings[$dateString . '|' . $slotKey] ?? null;
    if ($slotBooking !== null) {
        $refreshCandidates[] = $slotBooking->takeoverAt($takeoverMinutes);
    }
    $refreshCandidates[] = $bookingService->bookingDeadline($dateString, $slotKey);
    $refreshCandidates[] = DateHelper::fromDateString($dateString . ' ' . $slot['end']);
}
$upcomingChanges = array_filter($refreshCandidates, static fn (DateTimeImmutable $moment): bool => $moment > $now);
$nextChange = $upcomingChanges !== [] ? min($upcomingChanges) : $today->modify('+1 day');

$laundryTips = [
    'Ryst tøjet godt, inden du hænger det op. Det giver færre folder og kortere tørretid.',
    'Lad lågen til vaskemaskinen stå på klem efter brug, så maskinen kan tørre og holde sig frisk.',
    'Et ekstra centrifugeringsprogram kan forkorte tørretiden for håndklæder og sengetøj.',
    'Fyld ikke tromlen helt – der skal være cirka en håndsbredde fri plads i toppen.',
    'Vend mørkt tøj på vrangen før vask. Så holder farven sig pæn længere.',
    'Sortér efter både farve og materiale. Tunge håndklæder og let tøj tørrer bedst hver for sig.',
    'Tør gerne tøjet udenfor, når vejret tillader det – frisk luft er både gratis og skånsom.',
];
$laundryTip = $laundryTips[(int) $today->format('z') % count($laundryTips)];
$weatherForecast = (new WeatherService($settingModel))->getDryingForecast($settingsService->getWeatherPostcode(), $now);
$weatherVariant = $weatherForecast['variant'] ?? 'tip';
$weatherIcon = ['clear' => 'sun', 'mixed' => 'cloud-sun', 'rain' => 'cloud-rain', 'tip' => 'lightbulb'][$weatherVariant];

layout_start('Kalender', bodyClass: 'page-kiosk page-calendar', bodyData: [
    'refresh-at' => (string) ($nextChange->getTimestamp() * 1000),
]);
?>
<section
    class="daily-calendar"
    aria-labelledby="day-heading"
    data-swipe-area
    <?php if ($previousDate !== null): ?>data-previous-url="<?= e($calendarUrl($previousDate)) ?>"<?php endif; ?>
    <?php if ($nextDate !== null): ?>data-next-url="<?= e($calendarUrl($nextDate)) ?>"<?php endif; ?>
>
    <header class="day-header">
        <div class="day-title">
            <p class="day-eyebrow"><?= e($dayEyebrow) ?></p>
            <h1 id="day-heading"><?= e($dayTitle($selectedDate)) ?></h1>
        </div>
        <div class="day-actions">
            <?php if (!$isToday && $firstBookableDate == $today): ?>
                <a class="btn btn-secondary" href="<?= e($calendarUrl($today)) ?>"><?= e(t('I dag')) ?></a>
            <?php endif; ?>
            <button class="btn btn-secondary calendar-trigger" type="button" data-calendar-open aria-haspopup="dialog">
                <?= icon('calendar') ?>
                <span><?= e(t('Vælg dato')) ?></span>
            </button>
            <noscript>
            <form class="date-picker" method="get" action="/calendar.php">
                <label class="visually-hidden" for="calendar_date"><?= e(t('Vælg dato')) ?></label>
                <input
                    type="date"
                    id="calendar_date"
                    name="date"
                    value="<?= e($dateString) ?>"
                    min="<?= e($firstBookableDate->format('Y-m-d')) ?>"
                    max="<?= e($latestDate->format('Y-m-d')) ?>"
                >
                <button class="btn btn-secondary" type="submit"><?= e(t('Vis')) ?></button>
            </form>
            </noscript>
        </div>
    </header>

    <nav class="week-strip" aria-label="<?= e(t('Vælg dag')) ?>">
        <?php if ($previousDate !== null): ?>
            <a class="strip-arrow" href="<?= e($calendarUrl($previousDate)) ?>" aria-label="<?= e(t('Forrige dag')) ?>" data-previous-day><?= icon('chevron-left') ?></a>
        <?php else: ?>
            <span class="strip-arrow is-disabled" aria-hidden="true"><?= icon('chevron-left') ?></span>
        <?php endif; ?>
        <ol class="week-days">
            <?php for ($stripDate = $stripStart; $stripDate <= $stripEnd; $stripDate = $stripDate->modify('+1 day')): ?>
                <?php
                $stripDateString = $stripDate->format('Y-m-d');
                $isSelected = $stripDateString === $dateString;
                $stripBookable = $bookableByDate[$stripDateString] ?? null;
                $weekdayLabel = t($weekdayShort[(int) $stripDate->format('N') - 1]);
                ?>
                <li>
                <?php if ($stripBookable === null): ?>
                    <span class="day-chip is-outside" aria-hidden="true">
                        <span class="day-chip-weekday"><?= e($weekdayLabel) ?></span>
                        <span class="day-chip-date"><?= (int) $stripDate->format('j') ?></span>
                    </span>
                <?php else: ?>
                    <?php
                    $chipClasses = 'day-chip'
                        . ($isSelected ? ' is-selected' : '')
                        . ($stripDate == $today ? ' is-today' : '')
                        . (!in_array(true, $stripBookable, true) ? ' is-full' : '');
                    ?>
                    <a
                        class="<?= e($chipClasses) ?>"
                        href="<?= e($calendarUrl($stripDate)) ?>"
                        aria-label="<?= e(danish_date_long($stripDate) . ', ' . $availabilityLabel($stripDateString)) ?>"
                        <?= $isSelected ? 'aria-current="date"' : '' ?>
                    >
                        <span class="day-chip-weekday"><?= e($weekdayLabel) ?></span>
                        <span class="day-chip-date"><?= (int) $stripDate->format('j') ?></span>
                        <span class="availability-dots" aria-hidden="true">
                            <?php foreach ($stripBookable as $isFree): ?><span class="dot<?= $isFree ? ' is-free' : '' ?>"></span><?php endforeach; ?>
                        </span>
                    </a>
                <?php endif; ?>
                </li>
            <?php endfor; ?>
        </ol>
        <?php if ($nextDate !== null): ?>
            <a class="strip-arrow" href="<?= e($calendarUrl($nextDate)) ?>" aria-label="<?= e(t('Næste dag')) ?>" data-next-day><?= icon('chevron-right') ?></a>
        <?php else: ?>
            <span class="strip-arrow is-disabled" aria-hidden="true"><?= icon('chevron-right') ?></span>
        <?php endif; ?>
    </nav>

    <dialog class="calendar-dialog" data-calendar-dialog aria-labelledby="calendar-dialog-title" closedby="any">
        <div class="calendar-dialog-header">
            <button type="button" class="icon-button" data-month-previous aria-label="<?= e(t('Forrige måned')) ?>"><?= icon('chevron-left') ?></button>
            <h2 id="calendar-dialog-title" data-month-label><?= e(t('Vælg dag')) ?></h2>
            <button type="button" class="icon-button" data-month-next aria-label="<?= e(t('Næste måned')) ?>"><?= icon('chevron-right') ?></button>
            <button type="button" class="icon-button dialog-close" data-calendar-close aria-label="<?= e(t('Luk kalenderen')) ?>"><?= icon('close') ?></button>
        </div>
        <div class="calendar-weekdays" aria-hidden="true">
            <?php foreach ($weekdayShort as $weekday): ?><span><?= e(t($weekday)) ?></span><?php endforeach; ?>
        </div>
        <?php foreach ($calendarMonths as $calendarMonth): ?>
            <?php
            $monthKey = $calendarMonth->format('Y-m');
            $monthLabel = I18n::monthName((int) $calendarMonth->format('n')) . ' ' . $calendarMonth->format('Y');
            $leadingDays = (int) $calendarMonth->format('N') - 1;
            $daysInMonth = (int) $calendarMonth->format('t');
            $isSelectedMonth = $selectedDate->format('Y-m') === $monthKey;
            ?>
            <div class="calendar-month" data-calendar-month data-month-label="<?= e($monthLabel) ?>" <?= $isSelectedMonth ? 'data-active' : 'hidden' ?>>
                <?php for ($blank = 0; $blank < $leadingDays; $blank++): ?>
                    <span class="calendar-date is-blank" aria-hidden="true"></span>
                <?php endfor; ?>
                <?php for ($dayNumber = 1; $dayNumber <= $daysInMonth; $dayNumber++): ?>
                    <?php
                    $calendarDate = $calendarMonth->setDate((int) $calendarMonth->format('Y'), (int) $calendarMonth->format('n'), $dayNumber);
                    $calendarDateString = $calendarDate->format('Y-m-d');
                    $monthBookable = $bookableByDate[$calendarDateString] ?? null;
                    ?>
                    <?php if ($monthBookable !== null): ?>
                        <a
                            class="calendar-date<?= $calendarDateString === $dateString ? ' is-selected' : '' ?><?= !in_array(true, $monthBookable, true) ? ' is-full' : '' ?>"
                            href="<?= e($calendarUrl($calendarDate)) ?>"
                            aria-label="<?= e(danish_date_long($calendarDate) . ', ' . $availabilityLabel($calendarDateString)) ?>"
                            <?= $calendarDateString === $dateString ? 'aria-current="date"' : '' ?>
                        >
                            <span class="calendar-date-number"><?= $dayNumber ?></span>
                            <span class="availability-dots" aria-hidden="true">
                                <?php foreach ($monthBookable as $isFree): ?><span class="dot<?= $isFree ? ' is-free' : '' ?>"></span><?php endforeach; ?>
                            </span>
                        </a>
                    <?php else: ?>
                        <span class="calendar-date is-outside" aria-hidden="true"><span class="calendar-date-number"><?= $dayNumber ?></span></span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endforeach; ?>
        <p class="calendar-legend" aria-hidden="true">
            <span class="dot is-free"></span> <?= e(t('Ledig tid')) ?>
            <span class="dot"></span> <?= e(t('Booket eller overstået')) ?>
        </p>
    </dialog>

    <?php if ($calendarMessage !== ''): ?>
        <p class="calendar-message"><?= icon('megaphone') ?><span><?= e($calendarMessage) ?></span></p>
    <?php endif; ?>

    <ol class="day-slots" aria-label="<?= e(t('Vasketider for %s', danish_date_long($selectedDate))) ?>">
        <?php foreach ($slots as $slotKey => $slot): ?>
            <?php
            $booking = $bookings[$dateString . '|' . $slotKey] ?? null;
            $status = $slotStatus($dateString, $slotKey);
            include __DIR__ . '/../app/View/partials/slot_card.php';
            ?>
        <?php endforeach; ?>
    </ol>

    <aside class="weather-bar is-<?= e($weatherVariant) ?>" aria-labelledby="weather-heading">
        <span class="weather-icon" aria-hidden="true"><?= icon($weatherIcon) ?></span>
        <div class="weather-copy">
            <?php if ($weatherForecast !== null): ?>
                <h2 id="weather-heading"><?= e(match ($weatherVariant) { 'clear' => t('Godt tørrevejr'), 'rain' => t('Regnvejr'), default => t('Tørrevejr') }) ?> <?= e($weatherForecast['period']) ?></h2>
                <p><?= e($weatherForecast['recommendation']) ?></p>
                <ul class="weather-meta">
                    <li><?= icon('pin') ?><?= e($weatherForecast['location']) ?></li>
                    <li><?= icon('thermometer') ?><?= e(t('%d °C', (int) $weatherForecast['temperature'])) ?></li>
                    <li><?= icon('droplet') ?><?= e(t('%d %% risiko for regn', (int) $weatherForecast['rainProbability'])) ?></li>
                    <li><?= icon('wind') ?><?= e(t('%d m/s', (int) $weatherForecast['windSpeed'])) ?></li>
                    <li class="weather-source"><?= e(t('Vejrdata fra Open-Meteo')) ?></li>
                </ul>
            <?php else: ?>
                <h2 id="weather-heading"><?= e(t('Dagens vasketip')) ?></h2>
                <p><?= e(t($laundryTip)) ?></p>
            <?php endif; ?>
        </div>
        <?= illustration($weatherVariant, 'weather-illustration') ?>
    </aside>
</section>
<?php
layout_end();
