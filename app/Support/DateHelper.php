<?php

declare(strict_types=1);

namespace LaundryBooking\Support;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Date helpers for the Europe/Copenhagen timezone and Danish formatting.
 */
final class DateHelper
{
    private const array DAY_NAMES = [
        1 => 'mandag',
        2 => 'tirsdag',
        3 => 'onsdag',
        4 => 'torsdag',
        5 => 'fredag',
        6 => 'lørdag',
        7 => 'søndag',
    ];

    private const array MONTH_NAMES = [
        1 => 'januar',
        2 => 'februar',
        3 => 'marts',
        4 => 'april',
        5 => 'maj',
        6 => 'juni',
        7 => 'juli',
        8 => 'august',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    ];

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
        $day = self::DAY_NAMES[(int) $date->format('N')];
        $month = self::MONTH_NAMES[(int) $date->format('n')];

        return sprintf('%s %d. %s %s', $day, (int) $date->format('j'), $month, $date->format('Y'));
    }

    public static function formatShort(DateTimeImmutable $date): string
    {
        $day = self::DAY_NAMES[(int) $date->format('N')];

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
