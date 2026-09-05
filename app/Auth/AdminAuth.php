<?php

declare(strict_types=1);

namespace LaundryBooking\Auth;

use LaundryBooking\Security\RateLimiter;
use LaundryBooking\Support\Env;
use LogicException;

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

    private const int ACCOUNT_MAX_ATTEMPTS = 25;

    public function __construct(
        private readonly ?RateLimiter $rateLimiter = null,
        private readonly string $clientIdentifier = 'unknown'
    ) {
    }

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
        $limiter = $this->limiter();

        return $limiter->isLocked('admin_client', $this->clientSubject())
            || $limiter->isLocked('admin_account', $this->accountSubject());
    }

    public function lockoutRemainingSeconds(): int
    {
        $limiter = $this->limiter();

        return max(
            $limiter->remainingSeconds('admin_client', $this->clientSubject()),
            $limiter->remainingSeconds('admin_account', $this->accountSubject())
        );
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

        $this->limiter()->clear('admin_client', $this->clientSubject());
        $this->limiter()->clear('admin_account', $this->accountSubject());

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
        $limiter = $this->limiter();
        $limiter->recordFailure(
            'admin_client',
            $this->clientSubject(),
            self::MAX_ATTEMPTS,
            self::LOCKOUT_SECONDS,
            self::LOCKOUT_SECONDS
        );
        $limiter->recordFailure(
            'admin_account',
            $this->accountSubject(),
            self::ACCOUNT_MAX_ATTEMPTS,
            self::LOCKOUT_SECONDS,
            self::LOCKOUT_SECONDS
        );
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

    private function limiter(): RateLimiter
    {
        return $this->rateLimiter
            ?? throw new LogicException('A rate limiter is required for authentication attempts.');
    }

    private function clientSubject(): string
    {
        return $this->accountSubject() . '|' . $this->clientIdentifier;
    }

    private function accountSubject(): string
    {
        return Env::get('ADMIN_USERNAME', 'admin') ?? 'admin';
    }
}
