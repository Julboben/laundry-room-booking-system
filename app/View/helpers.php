<?php

declare(strict_types=1);

use LaundryBooking\Support\DateHelper;
use LaundryBooking\Support\I18n;

use function LaundryBooking\Support\e;

require_once __DIR__ . '/partials/illustration.php';

/**
 * View-layer helper functions. HTML escaping via e() is provided by
 * app/Support/Security.php, loaded through composer's files autoload.
 */

function danish_date_long(\DateTimeImmutable $date): string
{
    return DateHelper::formatLong($date);
}

/**
 * Formats a clock time in the locale's style: "07.00" in Danish, "07:00" in English.
 */
function clock_time(string $time): string
{
    $time = substr($time, 0, 5);

    return I18n::locale() === 'da' ? str_replace(':', '.', $time) : $time;
}

function clock_range(string $start, string $end): string
{
    return clock_time($start) . '–' . clock_time($end);
}

/**
 * Compact numeric date for admin tables, e.g. "24.09.2026" (da) or "24/09/2026" (en).
 */
function short_date(string $value): string
{
    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return date(I18n::locale() === 'da' ? 'd.m.Y' : 'd/m/Y', $timestamp);
}

function short_datetime(string $value): string
{
    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return short_date($value) . ' ' . clock_time(date('H:i', $timestamp));
}

/**
 * Renders an icon from the sprite in app/View/partials/icons.php.
 */
function icon(string $name, string $class = ''): string
{
    $classes = trim('icon ' . $class);

    return '<svg class="' . e($classes) . '" aria-hidden="true" focusable="false"><use href="#i-' . e($name) . '"></use></svg>';
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
