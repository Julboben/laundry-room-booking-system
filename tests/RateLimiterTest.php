<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Security\RateLimiter;
use PDO;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private RateLimiter $rateLimiter;

    protected function setUp(): void
    {
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

    public function testLockoutPersistsOutsideSessionState(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->rateLimiter->recordFailure('login', 'client', 3, 300, 300);
            $_SESSION = [];
        }

        $this->assertTrue($this->rateLimiter->isLocked('login', 'client'));
        $this->assertGreaterThan(0, $this->rateLimiter->remainingSeconds('login', 'client'));
    }

    public function testClearRemovesLockout(): void
    {
        $this->rateLimiter->recordFailure('login', 'client', 1, 300, 300);
        $this->assertTrue($this->rateLimiter->isLocked('login', 'client'));

        $this->rateLimiter->clear('login', 'client');

        $this->assertFalse($this->rateLimiter->isLocked('login', 'client'));
    }
}
