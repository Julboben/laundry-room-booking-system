<?php

declare(strict_types=1);

use function LaundryBooking\Support\e;

/**
 * Renders a single slot card for the calendar. Expects variables:
 * string $date, string $slotKey, array{start:string,end:string} $slot,
 * ?\LaundryBooking\Models\Booking $booking, string $csrfField (unused here).
 */
$isBooked = $booking !== null;
?>
<div class="slot-card <?= $isBooked ? 'slot-booked' : 'slot-available' ?>">
    <div class="slot-status">
        <?php if ($isBooked): ?>
            <div class="slot-label">
                <span class="slot-icon slot-icon-person" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20c.6-4 3-6 7-6s6.4 2 7 6"></path></svg>
                </span>
                <span class="slot-name"><?= e($booking->bookingName) ?></span>
            </div>
            <a class="btn btn-cancel" href="/cancel.php?id=<?= (int) $booking->id ?>">Aflys booking</a>
        <?php else: ?>
            <div class="slot-label">
                <span class="slot-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z"></path></svg>
                </span>
                <span class="slot-name">Ledig</span>
            </div>
            <a class="btn btn-book" href="/book.php?date=<?= e($date) ?>&amp;slot=<?= e($slotKey) ?>">Book tid</a>
        <?php endif; ?>
    </div>
</div>
