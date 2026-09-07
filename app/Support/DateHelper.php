<?php

declare(strict_types=1);

namespace LaundryBooking\Support;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Date helpers for the Europe/Copenhagen timezone and localized formatting.
 */
final class DateHelper
{
    public static function timezone(): DateTimeZone
    {
        static $timezone = null;

        if ($timezone === null) {
            $timezone = new DateTimeZone(Env::get('APP_TIMEZONE', 'Europe/Copenhagen') ?? 'Europe/Copenhagen');
        }

        return $timezone;
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::timezone());
    }

    public static function today(): DateTimeImmutable
    {
        return self::now()->setTime(0, 0);
    }

    /**
     * Returns the Monday of the week containing the given date.
     */
    public static function startOfWeek(DateTimeImmutable $date): DateTimeImmutable
    {
        $isoDay = (int) $date->format('N');
        return $date->modify('-' . ($isoDay - 1) . ' days')->setTime(0, 0);
    }

    public static function formatLong(DateTimeImmutable $date): string
    {
        $day = I18n::dayName((int) $date->format('N'));
        $month = I18n::monthName((int) $date->format('n'));

        if (I18n::locale() === 'en') {
            return sprintf('%s, %s %d, %s', $day, $month, (int) $date->format('j'), $date->format('Y'));
        }

        return sprintf('%s %d. %s %s', $day, (int) $date->format('j'), $month, $date->format('Y'));
    }

    public static function formatShort(DateTimeImmutable $date): string
    {
        $day = I18n::dayName((int) $date->format('N'));

        return sprintf('%s %d/%d', $day, (int) $date->format('j'), (int) $date->format('n'));
    }

    public static function isValidDateString(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    public static function fromDateString(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, self::timezone());
    }
}
