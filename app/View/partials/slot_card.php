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
?>
<div class="slot-card <?= $isPast ? 'slot-past' : ($isBooked ? 'slot-booked' : 'slot-available') ?>">
    <div class="slot-status">
        <?php if ($isPast): ?>
            <div class="slot-label">
                <span class="slot-icon slot-icon-past" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="m8.5 12 2.3 2.3 4.8-5"></path></svg>
                </span>
                <span class="slot-name">Passeret</span>
            </div>
            <span class="slot-unavailable">Kan ikke bookes</span>
        <?php elseif ($isBooked): ?>
            <div class="slot-label">
                <span class="slot-icon slot-icon-person" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20c.6-4 3-6 7-6s6.4 2 7 6"></path></svg>
                </span>
                <span class="slot-name"><?= e($booking->bookingName) ?></span>
            </div>
            <span class="btn btn-cancel slot-action">
                Aflys booking
                <svg class="slot-action-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
            </span>
        <?php else: ?>
            <div class="slot-label">
                <span class="slot-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z"></path></svg>
                </span>
                <span class="slot-name">Ledig</span>
            </div>
            <span class="btn btn-book slot-action">
                Book tid
                <svg class="slot-action-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
            </span>
        <?php endif; ?>
    </div>
</div>
