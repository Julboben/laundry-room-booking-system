<?php

declare(strict_types=1);

namespace LaundryBooking\Auth;

use LaundryBooking\Services\SettingsService;
use LaundryBooking\Support\Env;

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

    public function __construct(
        private readonly SettingsService $settings
    ) {
    }

    public function isAuthenticated(): bool
    {
        return ($_SESSION[self::SESSION_KEY] ?? false) === true;
    }

    public function isLockedOut(): bool
    {
        $lockedUntil = $_SESSION['resident_locked_until'] ?? null;

        return $lockedUntil !== null && time() < $lockedUntil;
    }

    public function lockoutRemainingSeconds(): int
    {
        $lockedUntil = $_SESSION['resident_locked_until'] ?? null;

        if ($lockedUntil === null) {
            return 0;
        }

        return max(0, $lockedUntil - time());
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

        $_SESSION['resident_failed_attempts'] = 0;
        unset($_SESSION['resident_locked_until']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION[self::SESSION_KEY] = true;

        return true;
    }

    private function registerFailure(): void
    {
        $attempts = (int) ($_SESSION['resident_failed_attempts'] ?? 0) + 1;
        $_SESSION['resident_failed_attempts'] = $attempts;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $_SESSION['resident_locked_until'] = time() + self::LOCKOUT_SECONDS;
        }
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}
