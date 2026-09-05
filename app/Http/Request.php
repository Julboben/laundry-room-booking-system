<?php

declare(strict_types=1);

namespace LaundryBooking\Http;

use LaundryBooking\Support\Env;

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
        if (Env::getBool('TRUST_PROXY_HEADERS', false)) {
            $realIp = $_SERVER['HTTP_X_REAL_IP'] ?? null;
            if (is_string($realIp) && filter_var($realIp, FILTER_VALIDATE_IP) !== false) {
                return $realIp;
            }
        }

        $value = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP) !== false
            ? $value
            : '0.0.0.0';
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
