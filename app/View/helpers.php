<?php

declare(strict_types=1);

use LaundryBooking\Support\DateHelper;

/**
 * View-layer helper functions. HTML escaping via e() is provided by
 * app/Support/Security.php, loaded through composer's files autoload.
 */

function danish_date_long(\DateTimeImmutable $date): string
{
    return DateHelper::formatLong($date);
}

function danish_date_short(\DateTimeImmutable $date): string
{
    return DateHelper::formatShort($date);
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $value;
}

function flash_set(string $key, string $value): void
{
    $_SESSION['flash'][$key] = $value;
}
