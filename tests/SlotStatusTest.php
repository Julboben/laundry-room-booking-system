<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Services\BookingService;
use LaundryBooking\Services\SlotStatus;
use LaundryBooking\Support\DateHelper;
use PHPUnit\Framework\TestCase;

final class SlotStatusTest extends TestCase
{
    private const string DATE = '2026-09-24';

    /** @var array{start:string,end:string} */
    private const array SLOT = ['start' => '07:00', 'end' => '10:00'];

    private function at(string $time): \DateTimeImmutable
    {
        return DateHelper::fromDateString(self::DATE . ' ' . $time);
    }

    public function testDeadlineIsStartPlusLateBookingWindow(): void
    {
        $deadline = BookingService::bookingDeadlineFor(self::DATE, self::SLOT, 120);

        $this->assertEquals($this->at('09:00'), $deadline);
    }

    public function testDeadlineNeverExceedsSlotEnd(): void
    {
        $deadline = BookingService::bookingDeadlineFor(self::DATE, self::SLOT, 600);

        $this->assertEquals($this->at('10:00'), $deadline);
    }

    public function testZeroMinutesKeepsBookingBeforeStartOnly(): void
    {
        $this->assertSame(SlotStatus::Available, BookingService::statusFor(self::DATE, self::SLOT, false, $this->at('06:59'), 0));
        $this->assertSame(SlotStatus::Closed, BookingService::statusFor(self::DATE, self::SLOT, false, $this->at('07:00'), 0));
    }

    public function testFreeStartedSlotIsBookableInsideWindow(): void
    {
        $status = BookingService::statusFor(self::DATE, self::SLOT, false, $this->at('07:38'), 120);

        $this->assertSame(SlotStatus::AvailableNow, $status);
        $this->assertTrue($status->isBookable());
    }

    public function testFreeStartedSlotClosesAfterWindow(): void
    {
        $status = BookingService::statusFor(self::DATE, self::SLOT, false, $this->at('09:00'), 120);

        $this->assertSame(SlotStatus::Closed, $status);
        $this->assertFalse($status->isBookable());
    }

    public function testBookedSlotStates(): void
    {
        $this->assertSame(SlotStatus::Booked, BookingService::statusFor(self::DATE, self::SLOT, true, $this->at('06:00'), 120));
        $this->assertSame(SlotStatus::InProgress, BookingService::statusFor(self::DATE, self::SLOT, true, $this->at('08:00'), 120));
    }

    public function testEndedSlotIsPast(): void
    {
        $this->assertSame(SlotStatus::Past, BookingService::statusFor(self::DATE, self::SLOT, false, $this->at('10:00'), 120));
        $this->assertSame(SlotStatus::Past, BookingService::statusFor(self::DATE, self::SLOT, true, $this->at('11:00'), 120));
    }
}
