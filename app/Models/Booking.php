<?php

declare(strict_types=1);

namespace LaundryBooking\Models;

use DateTimeImmutable;
use LaundryBooking\Support\DateHelper;

/**
 * Simple read-model representing a row from the bookings table.
 * The cancellation_code_hash is intentionally never exposed here.
 */
final class Booking
{
    public function __construct(
        public readonly int $id,
        public readonly string $bookingDate,
        public readonly string $slotKey,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly string $bookingName,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            bookingDate: (string) $row['booking_date'],
            slotKey: (string) $row['slot_key'],
            startTime: (string) $row['start_time'],
            endTime: (string) $row['end_time'],
            bookingName: (string) $row['booking_name'],
            createdAt: (string) $row['created_at'],
        );
    }

    /**
     * When other residents may take over an unused slot: counted from the
     * slot start, or from the booking time if it was booked after the start.
     */
    public function takeoverAt(int $takeoverMinutes): DateTimeImmutable
    {
        $slotStart = DateHelper::fromDateString($this->bookingDate . ' ' . $this->startTime);
        $bookedAt = DateHelper::fromDateString($this->createdAt);

        return max($slotStart, $bookedAt)->modify('+' . $takeoverMinutes . ' minutes');
    }
}
