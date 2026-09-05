<?php

declare(strict_types=1);

namespace LaundryBooking\Http;

/**
 * CSRF token generation and verification backed by the PHP session.
 */
final class Csrf
{
    private const string SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        $token = self::token();

        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $submittedToken): bool
    {
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($sessionToken) || !is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }
}
