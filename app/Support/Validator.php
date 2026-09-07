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
            return I18n::translate('Skriv et navn.');
        }

        if (mb_strlen($trimmed) < 2) {
            return I18n::translate('Navnet er for kort.');
        }

        if (mb_strlen($trimmed) > 100) {
            return I18n::translate('Navnet må højst være 100 tegn.');
        }

        return null;
    }

    public static function normalizeName(string $name): string
    {
        return trim($name);
    }

    public static function validateCancellationCodeFormat(string $code, int $length = 4): ?string
    {
        if (trim($code) === '') {
            return I18n::translate('Skriv aflysningskoden.');
        }

        if (preg_match('/^\d{' . $length . '}$/', trim($code)) !== 1) {
            return I18n::translate('Aflysningskoden skal bestå af %d cifre.', $length);
        }

        return null;
    }

    public static function validateSlotKey(string $slotKey): ?string
    {
        if (!array_key_exists($slotKey, \LaundryBooking\Services\BookingService::slots())) {
            return I18n::translate('Tiden er ikke gyldig.');
        }

        return null;
    }

    public static function validateDateFormat(string $date): ?string
    {
        if (!DateHelper::isValidDateString($date)) {
            return I18n::translate('Datoen er ikke gyldig.');
        }

        return null;
    }
}
