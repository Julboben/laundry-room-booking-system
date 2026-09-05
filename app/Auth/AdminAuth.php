<?php

declare(strict_types=1);

namespace LaundryBooking\Auth;

use LaundryBooking\Support\Env;

/**
 * Separate authentication for administrators. Uses its own session
 * flag, rate limiting, and a username/password-hash pair configured
 * through the environment.
 */
final class AdminAuth
{
    private const string SESSION_KEY = 'admin_authenticated';

    private const string SESSION_USERNAME_KEY = 'admin_username';

    private const int MAX_ATTEMPTS = 5;

    private const int LOCKOUT_SECONDS = 300;

    public function isAuthenticated(): bool
    {
        return ($_SESSION[self::SESSION_KEY] ?? false) === true;
    }

    public function currentUsername(): ?string
    {
        return $_SESSION[self::SESSION_USERNAME_KEY] ?? null;
    }

    public function isLockedOut(): bool
    {
        $lockedUntil = $_SESSION['admin_locked_until'] ?? null;

        return $lockedUntil !== null && time() < $lockedUntil;
    }

    public function lockoutRemainingSeconds(): int
    {
        $lockedUntil = $_SESSION['admin_locked_until'] ?? null;

        if ($lockedUntil === null) {
            return 0;
        }

        return max(0, $lockedUntil - time());
    }

    public function attempt(string $username, string $password): bool
    {
        if ($this->isLockedOut()) {
            return false;
        }

        $expectedUsername = Env::get('ADMIN_USERNAME', 'admin') ?? 'admin';
        $expectedHash = Env::get('ADMIN_PASSWORD_HASH', '') ?? '';

        $usernameMatches = hash_equals($expectedUsername, $username);
        $passwordMatches = $expectedHash !== '' && password_verify($password, $expectedHash);

        if (!$usernameMatches || !$passwordMatches) {
            $this->registerFailure();
            return false;
        }

        $_SESSION['admin_failed_attempts'] = 0;
        unset($_SESSION['admin_locked_until']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION[self::SESSION_USERNAME_KEY] = $username;
        $_SESSION['admin_last_activity'] = time();

        return true;
    }

    private function registerFailure(): void
    {
        $attempts = (int) ($_SESSION['admin_failed_attempts'] ?? 0) + 1;
        $_SESSION['admin_failed_attempts'] = $attempts;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $_SESSION['admin_locked_until'] = time() + self::LOCKOUT_SECONDS;
        }
    }

    /**
     * Admin sessions use a stricter idle timeout than resident sessions.
     */
    public function hasTimedOut(int $idleSeconds = 1800): bool
    {
        $lastActivity = $_SESSION['admin_last_activity'] ?? null;

        if ($lastActivity === null) {
            return false;
        }

        return (time() - $lastActivity) > $idleSeconds;
    }

    public function touch(): void
    {
        $_SESSION['admin_last_activity'] = time();
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::SESSION_USERNAME_KEY], $_SESSION['admin_last_activity']);
    }
}
