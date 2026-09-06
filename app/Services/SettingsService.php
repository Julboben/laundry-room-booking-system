<?php

declare(strict_types=1);

namespace LaundryBooking\Services;

use LaundryBooking\Models\Setting;
use LaundryBooking\Support\Env;

/**
 * Reads and writes configurable settings. Falls back to the .env
 * defaults when a value has never been changed via the admin panel.
 */
final class SettingsService
{
    public function __construct(
        private readonly Setting $settings
    ) {
    }

    public function getPropertyCodeHash(): ?string
    {
        $value = $this->settings->get('resident_property_code_hash');

        if ($value !== null && $value !== '') {
            return $value;
        }

        return Env::get('RESIDENT_PROPERTY_CODE_HASH');
    }

    public function setPropertyCodeHash(string $hash): void
    {
        $this->settings->set('resident_property_code_hash', $hash);
    }

    public function getBookingWeeksAhead(): int
    {
        $value = $this->settings->get('booking_weeks_ahead');

        if ($value !== null && $value !== '') {
            return (int) $value;
        }

        return Env::getInt('BOOKING_WEEKS_AHEAD', 8);
    }

    public function setBookingWeeksAhead(int $weeks): void
    {
        $this->settings->set('booking_weeks_ahead', (string) $weeks);
    }

    public function getTakeoverRuleMinutes(): int
    {
        $value = $this->settings->get('takeover_rule_minutes');

        return $value !== null && $value !== '' ? (int) $value : 30;
    }

    public function getCalendarMessage(): string
    {
        return $this->settings->get('calendar_message') ?? '';
    }

    public function setCalendarMessage(string $message): void
    {
        $this->settings->set('calendar_message', $message);
    }

    public function getWeatherPostcode(): string
    {
        $value = trim($this->settings->get('weather_postcode') ?? '');

        return preg_match('/^\d{4}$/', $value) === 1 ? $value : '1352';
    }

    public function setWeatherPostcode(string $postcode): void
    {
        $this->settings->set('weather_postcode', $postcode);
    }

    public function getCancellationCodeLength(): int
    {
        $length = (int) ($this->settings->get('cancellation_code_length') ?? '4');

        return max(4, min(8, $length));
    }

    public function setCancellationCodeLength(int $length): void
    {
        $this->settings->set('cancellation_code_length', (string) $length);
    }
}
