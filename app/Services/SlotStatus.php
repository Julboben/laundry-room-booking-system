<?php

declare(strict_types=1);

namespace LaundryBooking\Services;

/**
 * Display and booking state of a single slot at a given moment.
 */
enum SlotStatus: string
{
    /** Free and not yet started. */
    case Available = 'available';

    /** Free, already started, but still inside the late-booking window. */
    case AvailableNow = 'available-now';

    /** Booked and not yet started. */
    case Booked = 'booked';

    /** Booked and currently running. */
    case InProgress = 'in-progress';

    /** Free and running, but the late-booking window has closed. */
    case Closed = 'closed';

    /** The slot has ended. */
    case Past = 'past';

    public function isBookable(): bool
    {
        return $this === self::Available || $this === self::AvailableNow;
    }

    public function hasBooking(): bool
    {
        return $this === self::Booked || $this === self::InProgress;
    }
}
