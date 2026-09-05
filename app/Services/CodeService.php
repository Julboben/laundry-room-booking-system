<?php

declare(strict_types=1);

namespace LaundryBooking\Services;

/**
 * Cryptographically secure, human-readable cancellation tokens.
 */
final class CodeService
{
    private const string ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const int TOKEN_LENGTH = 16;

    public function generateCancellationCode(): string
    {
        $characters = '';
        $maxIndex = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < self::TOKEN_LENGTH; $index++) {
            $characters .= self::ALPHABET[random_int(0, $maxIndex)];
        }

        return implode('-', str_split($characters, 4));
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
        return strtoupper(str_replace(['-', ' '], '', trim($code)));
    }
}
