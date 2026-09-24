<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Models\Booking;
use PHPUnit\Framework\TestCase;

final class BookingTakeoverTest extends TestCase
{
    public function testTakeoverCountsFromSlotStartWhenBookedInAdvance(): void
    {
        $booking = $this->booking('2026-09-23 20:15:00');

        self::assertSame('2026-09-24 07:30', $booking->takeoverAt(30)->format('Y-m-d H:i'));
    }

    public function testTakeoverCountsFromBookingTimeWhenBookedAfterStart(): void
    {
        $booking = $this->booking('2026-09-24 08:02:00');

        self::assertSame('2026-09-24 08:32', $booking->takeoverAt(30)->format('Y-m-d H:i'));
    }

    private function booking(string $createdAt): Booking
    {
        return new Booking(
            id: 1,
            bookingDate: '2026-09-24',
            slotKey: '07-10',
            startTime: '07:00:00',
            endTime: '10:00:00',
            bookingName: 'Test',
            createdAt: $createdAt,
        );
    }
}
