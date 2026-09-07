<?php

declare(strict_types=1);

use function LaundryBooking\Support\e;

/**
 * Renders a single slot card for the calendar. Expects variables:
 * string $date, string $slotKey, array{start:string,end:string} $slot,
 * ?\LaundryBooking\Models\Booking $booking, bool $isPast.
 */
$isBooked = $booking !== null;
$isPast = $isPast ?? false;
[$startHour, $startMinute] = array_map('intval', explode(':', $slot['start']));
[$endHour, $endMinute] = array_map('intval', explode(':', $slot['end']));
$durationMinutes = (($endHour * 60) + $endMinute) - (($startHour * 60) + $startMinute);
$durationLabel = $durationMinutes === 60 ? '1 time' : (int) ($durationMinutes / 60) . ' timer';
?>
<div class="slot-card <?= $isPast ? 'slot-past' : ($isBooked ? 'slot-booked' : 'slot-available') ?>">
    <div class="slot-status">
        <?php if ($isPast): ?>
            <span class="slot-content">
                <span class="slot-hero-icon slot-hero-past" aria-hidden="true">
                    <svg viewBox="0 0 32 32"><circle cx="16" cy="16" r="11"></circle><path d="m11.5 16 3 3 6.5-7"></path></svg>
                </span>
                <span class="slot-copy">
                    <span class="slot-name">Passeret</span>
                </span>
            </span>
            <span class="slot-unavailable">Kan ikke bookes</span>
            <span class="slot-duration is-placeholder" aria-hidden="true"><?= e($durationLabel) ?></span>
        <?php elseif ($isBooked): ?>
            <span class="slot-content">
                <span class="slot-hero-icon slot-hero-person" aria-hidden="true">
                    <svg viewBox="0 0 32 32"><circle cx="16" cy="10.5" r="4.5"></circle><path d="M7.5 27c.8-6 3.7-9 8.5-9s7.7 3 8.5 9"></path></svg>
                </span>
                <span class="slot-copy">
                    <span class="slot-name"><?= e($booking->bookingName) ?></span>
                    <span class="slot-badge">Optaget</span>
                </span>
            </span>
            <span class="btn btn-cancel slot-action">Aflys booking</span>
            <span class="slot-duration is-placeholder" aria-hidden="true"><?= e($durationLabel) ?></span>
        <?php else: ?>
            <span class="slot-content">
                <span class="slot-hero-icon slot-hero-available" aria-hidden="true">
                    <svg viewBox="0 0 32 32"><path d="M9 4v4M23 4v4M5 11h22M8 6h16a3 3 0 0 1 3 3v18H5V9a3 3 0 0 1 3-3Z"></path><path d="m11 19 3 3 7-7"></path></svg>
                </span>
                <span class="slot-copy">
                    <span class="slot-name">Ledig</span>
                </span>
            </span>
            <span class="btn btn-book slot-action">Book tid</span>
            <span class="slot-duration">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l3 2"></path></svg>
                <?= e($durationLabel) ?>
            </span>
        <?php endif; ?>
    </div>
</div>
