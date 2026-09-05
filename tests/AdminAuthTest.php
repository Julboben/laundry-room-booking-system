<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Auth\AdminAuth;
use LaundryBooking\Support\Env;
use PHPUnit\Framework\TestCase;

final class AdminAuthTest extends TestCase
{
    protected function setUp(): void
    {
        Env::reset();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION = [];

        putenv('ADMIN_USERNAME=admin');
        putenv('ADMIN_PASSWORD_HASH=' . password_hash('correct-horse', PASSWORD_DEFAULT));
    }

    public function testCorrectCredentialsAuthenticate(): void
    {
        $auth = new AdminAuth();

        $this->assertTrue($auth->attempt('admin', 'correct-horse'));
        $this->assertTrue($auth->isAuthenticated());
    }

    public function testIncorrectCredentialsAreRejected(): void
    {
        $auth = new AdminAuth();

        $this->assertFalse($auth->attempt('admin', 'wrong-password'));
        $this->assertFalse($auth->isAuthenticated());
    }

    public function testLocksOutAfterFiveFailedAttempts(): void
    {
        $auth = new AdminAuth();

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($auth->attempt('admin', 'wrong-password'));
        }

        $this->assertTrue($auth->isLockedOut());
        $this->assertGreaterThan(0, $auth->lockoutRemainingSeconds());

        // Even the correct password is rejected while locked out.
        $this->assertFalse($auth->attempt('admin', 'correct-horse'));
        $this->assertFalse($auth->isAuthenticated());
    }
}
