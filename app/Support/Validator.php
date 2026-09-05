<?php

declare(strict_types=1);

namespace LaundryBooking\Support;

/**
 * Simple validation result value object.
 */
final class ValidationResult
{
    /**
     * @param array<string,string> $errors field => message
     */
    public function __construct(
        private readonly array $errors = []
    ) {
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * @return array<string,string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): ?string
    {
        $values = array_values($this->errors);
        return $values[0] ?? null;
    }
}

/**
 * Stateless validation helpers used across the resident-facing forms.
 */
final class Validator
{
    public static function validateName(string $name): ?string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return 'Skriv et navn.';
        }

        if (mb_strlen($trimmed) < 2) {
            return 'Navnet er for kort.';
        }

        if (mb_strlen($trimmed) > 100) {
            return 'Navnet må højst være 100 tegn.';
        }

        return null;
    }

    public static function normalizeName(string $name): string
    {
        return trim($name);
    }

    public static function validateCancellationCodeFormat(string $code): ?string
    {
        if (trim($code) === '') {
            return 'Skriv aflysningskoden.';
        }

        $normalized = strtoupper(str_replace(['-', ' '], '', trim($code)));

        $isLegacyCode = preg_match('/^\d{6}$/', $normalized) === 1;
        $isCurrentCode = preg_match('/^[2-9A-HJ-NP-Z]{16}$/', $normalized) === 1;

        if (!$isLegacyCode && !$isCurrentCode) {
            return 'Aflysningskoden har ikke et gyldigt format.';
        }

        return null;
    }

    public static function validateSlotKey(string $slotKey): ?string
    {
        if (!array_key_exists($slotKey, \LaundryBooking\Services\BookingService::slots())) {
            return 'Tiden er ikke gyldig.';
        }

        return null;
    }

    public static function validateDateFormat(string $date): ?string
    {
        if (!DateHelper::isValidDateString($date)) {
            return 'Datoen er ikke gyldig.';
        }

        return null;
    }
}
