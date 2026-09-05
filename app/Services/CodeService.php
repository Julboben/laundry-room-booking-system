<?php

declare(strict_types=1);

namespace LaundryBooking\Services;

/**
 * Cryptographically secure six-digit code generation and hashing.
 */
final class CodeService
{
    public function generateSixDigitCode(): string
    {
        return str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    public function hashCode(string $code): string
    {
        return password_hash($code, PASSWORD_DEFAULT);
    }

    public function verifyCode(string $code, string $hash): bool
    {
        return password_verify($code, $hash);
    }
}
