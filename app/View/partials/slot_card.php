<?php

declare(strict_types=1);

use LaundryBooking\Services\SlotStatus;
use LaundryBooking\Support\DateHelper;

use function LaundryBooking\Support\e;
use function LaundryBooking\Support\t;
use function LaundryBooking\Support\tn;

/**
 * Renders one slot of the day view as an <li>. Expects:
 * string $dateString, string $slotKey, array{start:string,end:string} $slot,
 * ?\LaundryBooking\Models\Booking $booking, SlotStatus $status,
 * DateTimeImmutable $now, int $takeoverMinutes,
 * \LaundryBooking\Services\BookingService $bookingService.
 */
$slotStart = DateHelper::fromDateString($dateString . ' ' . $slot['start']);
$slotEnd = DateHelper::fromDateString($dateString . ' ' . $slot['end']);
$durationMinutes = (int) (($slotEnd->getTimestamp() - $slotStart->getTimestamp()) / 60);
$durationLabel = tn(intdiv($durationMinutes, 60), '%d time', '%d timer');
$bookUrl = '/book.php?date=' . rawurlencode($dateString) . '&slot=' . rawurlencode($slotKey);
$cancelUrl = $booking !== null ? '/cancel.php?id=' . $booking->id . '&date=' . rawurlencode($dateString) : null;
$timeMarkup = '<span class="slot-time">' . icon('clock') . '<span>'
    . '<time datetime="' . e($slotStart->format('Y-m-d\TH:i')) . '">' . e(clock_time($slot['start'])) . '</time>–'
    . '<time datetime="' . e($slotEnd->format('Y-m-d\TH:i')) . '">' . e(clock_time($slot['end'])) . '</time></span></span>';
?>
<li class="slot is-<?= e($status->value) ?>">
<?php if ($status->isBookable()): ?>
    <?php $deadline = $bookingService->bookingDeadline($dateString, $slotKey); ?>
    <a class="slot-card" href="<?= e($bookUrl) ?>" aria-label="<?= e(t('Book tiden fra %s til %s', clock_time($slot['start']), clock_time($slot['end']))) ?>">
        <?= $timeMarkup ?>
        <span class="slot-body">
            <span class="slot-icon"><?= icon('washer') ?></span>
            <?php if ($status === SlotStatus::AvailableNow): ?>
                <span class="slot-badge"><?= e(t('Ledig nu')) ?></span>
                <span class="slot-title"><?= e(t('Tiden er i gang')) ?></span>
                <span class="slot-meta"><?= e(t('Kan bookes til kl. %s', clock_time($deadline->format('H:i')))) ?></span>
            <?php else: ?>
                <span class="slot-title"><?= e(t('Ledig')) ?></span>
                <span class="slot-meta"><?= e($durationLabel) ?> · <?= e(t('Vaskemaskine og tørretumbler')) ?></span>
            <?php endif; ?>
        </span>
        <span class="slot-action"><?= e(t('Book tid')) ?><?= icon('chevron-right') ?></span>
    </a>
<?php elseif ($status->hasBooking() && $booking !== null): ?>
    <div class="slot-card">
        <?= $timeMarkup ?>
        <span class="slot-body">
            <span class="slot-icon"><?= icon($status === SlotStatus::InProgress ? 'timer' : 'user') ?></span>
            <span class="slot-badge"><?= e($status === SlotStatus::InProgress ? t('I gang nu') : t('Booket')) ?></span>
            <span class="slot-title"><?= e($booking->bookingName) ?></span>
            <?php if ($status === SlotStatus::InProgress): ?>
                <?php
                $elapsedMinutes = (int) (($now->getTimestamp() - $slotStart->getTimestamp()) / 60);
                $takeoverAt = $booking->takeoverAt($takeoverMinutes);
                ?>
                <progress class="slot-progress" max="<?= $durationMinutes ?>" value="<?= $elapsedMinutes ?>" aria-label="<?= e(t('Tid brugt')) ?>"></progress>
                <span class="slot-meta">
                    <?= e($now < $takeoverAt
                        ? t('Kan overtages kl. %s, hvis tiden ikke er taget i brug', clock_time($takeoverAt->format('H:i')))
                        : t('Står maskinerne stille? Så må tiden overtages.')) ?>
                </span>
            <?php else: ?>
                <span class="slot-meta"><?= e($durationLabel) ?></span>
            <?php endif; ?>
        </span>
        <a class="slot-action slot-cancel" href="<?= e((string) $cancelUrl) ?>" aria-label="<?= e(t('Aflys bookingen for %s fra %s til %s', $booking->bookingName, clock_time($slot['start']), clock_time($slot['end']))) ?>">
            <?= e(t('Aflys')) ?>
        </a>
    </div>
<?php else: ?>
    <div class="slot-card">
        <?= $timeMarkup ?>
        <span class="slot-body">
            <span class="slot-icon"><?= icon($status === SlotStatus::Closed ? 'lock' : 'clock') ?></span>
            <span class="slot-title"><?= e($status === SlotStatus::Closed ? t('Lukket for booking') : t('Overstået')) ?></span>
            <span class="slot-meta"><?= e($status === SlotStatus::Closed ? t('Tiden er i gang og kan ikke længere bookes') : t('Kan ikke bookes')) ?></span>
        </span>
    </div>
<?php endif; ?>
</li>
