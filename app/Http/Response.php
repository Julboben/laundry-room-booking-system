<?php

declare(strict_types=1);

namespace LaundryBooking\Http;

/**
 * Response helpers for redirects and JSON output.
 */
final class Response
{
    public static function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function json(array $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function securityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header(
            "Content-Security-Policy: default-src 'self'; " .
            "script-src 'self'; style-src 'self'; " .
            "img-src 'self' data:; base-uri 'self'; form-action 'self'"
        );
    }
}
