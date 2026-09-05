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
    <div class="slot-time"><?= e($slot['start']) ?>&ndash;<?= e($slot['end']) ?></div>
    <div class="slot-status">
        <?php if ($isBooked): ?>
            <span class="badge badge-booked">Booket</span>
            <span class="slot-name"><?= e($booking->bookingName) ?></span>
            <a class="btn btn-secondary" href="/cancel.php?id=<?= (int) $booking->id ?>">Aflys booking</a>
        <?php else: ?>
            <span class="badge badge-available">Ledig</span>
            <a class="btn btn-primary" href="/book.php?date=<?= e($date) ?>&amp;slot=<?= e($slotKey) ?>">Book tid</a>
        <?php endif; ?>
    </div>
</div>
