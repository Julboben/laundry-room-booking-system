<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Auth\AdminAuth;
use LaundryBooking\Security\RateLimiter;
use LaundryBooking\Support\Env;
use PDO;
use PHPUnit\Framework\TestCase;

final class AdminAuthTest extends TestCase
{
    private RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        Env::reset();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION = [];

        putenv('ADMIN_USERNAME=admin');
        putenv('ADMIN_PASSWORD_HASH=' . password_hash('correct-horse', PASSWORD_DEFAULT));

        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(
            'CREATE TABLE rate_limits (
                scope TEXT NOT NULL,
                subject_hash TEXT NOT NULL,
                failed_attempts INTEGER NOT NULL DEFAULT 0,
                window_started_at INTEGER NOT NULL,
                locked_until INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (scope, subject_hash)
            )'
        );
        $this->rateLimiter = new RateLimiter($pdo);
    }

    public function testCorrectCredentialsAuthenticate(): void
    {
        $auth = new AdminAuth($this->rateLimiter, '192.0.2.10');

        $this->assertTrue($auth->attempt('admin', 'correct-horse'));
        $this->assertTrue($auth->isAuthenticated());
    }

    public function testIncorrectCredentialsAreRejected(): void
    {
        $auth = new AdminAuth($this->rateLimiter, '192.0.2.10');

        $this->assertFalse($auth->attempt('admin', 'wrong-password'));
        $this->assertFalse($auth->isAuthenticated());
    }

    public function testLocksOutAfterFiveFailedAttempts(): void
    {
        $auth = new AdminAuth($this->rateLimiter, '192.0.2.10');

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($auth->attempt('admin', 'wrong-password'));
            $_SESSION = [];
        }

        $this->assertTrue($auth->isLockedOut());
        $this->assertGreaterThan(0, $auth->lockoutRemainingSeconds());

        // Even the correct password is rejected while locked out.
        $this->assertFalse($auth->attempt('admin', 'correct-horse'));
        $this->assertFalse($auth->isAuthenticated());
    }
}
