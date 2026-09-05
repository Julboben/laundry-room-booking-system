<?php

declare(strict_types=1);

namespace LaundryBooking\Auth;

use LaundryBooking\Security\RateLimiter;
use LaundryBooking\Services\SettingsService;
use LogicException;

/**
 * Handles the shared property-code access used by residents. There are
 * no individual resident accounts; a single hashed code protects the
 * calendar for the whole property.
 */
final class ResidentAccess
{
    private const string SESSION_KEY = 'resident_authenticated';

    private const int MAX_ATTEMPTS = 5;

    private const int LOCKOUT_SECONDS = 300;

    private const int PROPERTY_MAX_ATTEMPTS = 50;

    public function __construct(
        private readonly SettingsService $settings,
        private readonly ?RateLimiter $rateLimiter = null,
        private readonly string $clientIdentifier = 'unknown'
    ) {
    }

    public function isAuthenticated(): bool
    {
        if (($_SESSION[self::SESSION_KEY] ?? false) !== true) {
            return false;
        }

        $sessionFingerprint = $_SESSION['resident_code_fingerprint'] ?? null;
        $currentFingerprint = $this->codeFingerprint();

        return is_string($sessionFingerprint)
            && $currentFingerprint !== null
            && hash_equals($currentFingerprint, $sessionFingerprint);
    }

    public function isLockedOut(): bool
    {
        $limiter = $this->limiter();

        return $limiter->isLocked('resident_client', $this->clientIdentifier)
            || $limiter->isLocked('resident_property', 'shared');
    }

    public function lockoutRemainingSeconds(): int
    {
        $limiter = $this->limiter();

        return max(
            $limiter->remainingSeconds('resident_client', $this->clientIdentifier),
            $limiter->remainingSeconds('resident_property', 'shared')
        );
    }

    public function attempt(string $code): bool
    {
        if ($this->isLockedOut()) {
            return false;
        }

        $hash = $this->settings->getPropertyCodeHash();

        if ($hash === null || $hash === '' || !password_verify($code, $hash)) {
            $this->registerFailure();
            return false;
        }

        $this->limiter()->clear('resident_client', $this->clientIdentifier);
        $this->limiter()->clear('resident_property', 'shared');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION['resident_code_fingerprint'] = hash('sha256', $hash);

        return true;
    }

    private function registerFailure(): void
    {
        $limiter = $this->limiter();
        $limiter->recordFailure(
            'resident_client',
            $this->clientIdentifier,
            self::MAX_ATTEMPTS,
            self::LOCKOUT_SECONDS,
            self::LOCKOUT_SECONDS
        );
        $limiter->recordFailure(
            'resident_property',
            'shared',
            self::PROPERTY_MAX_ATTEMPTS,
            self::LOCKOUT_SECONDS,
            self::LOCKOUT_SECONDS
        );
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_SESSION['resident_code_fingerprint']);
    }

    private function limiter(): RateLimiter
    {
        return $this->rateLimiter
            ?? throw new LogicException('A rate limiter is required for authentication attempts.');
    }

    private function codeFingerprint(): ?string
    {
        $hash = $this->settings->getPropertyCodeHash();

        return $hash === null || $hash === '' ? null : hash('sha256', $hash);
    }
}
