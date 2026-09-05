<?php

declare(strict_types=1);

namespace LaundryBooking\Http;

/**
 * Thin wrapper around superglobals to make request access explicit
 * and testable.
 */
final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public static function post(string $key, ?string $default = null): ?string
    {
        $value = $_POST[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public static function ip(): string
    {
        $value = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return is_string($value) ? $value : '0.0.0.0';
    }

    public static function userAgent(): ?string
    {
        $value = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return mb_substr($value, 0, 255);
    }
}
