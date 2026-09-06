<?php

declare(strict_types=1);

namespace LaundryBooking\Services;

/**
 * Generates and verifies numeric cancellation codes using a
 * cryptographically secure random source.
 */
final class CodeService
{
    public function generateCancellationCode(int $length = 4): string
    {
        if ($length < 4 || $length > 8) {
            throw new \InvalidArgumentException('Cancellation code length must be between 4 and 8 digits.');
        }

        $maximum = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $maximum), $length, '0', STR_PAD_LEFT);
    }

    public function hashCode(string $code): string
    {
        return password_hash($this->normalize($code), PASSWORD_DEFAULT);
    }

    public function verifyCode(string $code, string $hash): bool
    {
        return password_verify($this->normalize($code), $hash);
    }

    public function normalize(string $code): string
    {
        return trim($code);
    }
}
