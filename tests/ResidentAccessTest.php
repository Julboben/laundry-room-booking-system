<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Auth\ResidentAccess;
use LaundryBooking\Models\Setting;
use LaundryBooking\Security\RateLimiter;
use LaundryBooking\Services\SettingsService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ResidentAccessTest extends TestCase
{
    private SettingsService $settingsService;

    private RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION = [];

        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE settings (setting_key TEXT PRIMARY KEY, setting_value TEXT NOT NULL)');
        $pdo->exec(
            "INSERT INTO settings (setting_key, setting_value) VALUES ('resident_property_code_hash', "
            . $pdo->quote(password_hash('1234', PASSWORD_DEFAULT)) . ')'
        );
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

        $settings = new Setting($pdo);
        $this->settingsService = new SettingsService($settings);
        $this->rateLimiter = new RateLimiter($pdo);
    }

    public function testCorrectCodeGrantsAccess(): void
    {
        $access = $this->access();

        $this->assertTrue($access->attempt('1234'));
        $this->assertTrue($access->isAuthenticated());
    }

    public function testIncorrectCodeIsRejected(): void
    {
        $access = $this->access();

        $this->assertFalse($access->attempt('0000'));
        $this->assertFalse($access->isAuthenticated());
    }

    public function testLocksOutAfterFiveFailedAttempts(): void
    {
        $access = $this->access();

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($access->attempt('0000'));
            $_SESSION = [];
        }

        $this->assertTrue($access->isLockedOut());
        $this->assertGreaterThan(0, $access->lockoutRemainingSeconds());
        $this->assertFalse($access->attempt('1234'));
    }

    public function testLogoutClearsAccess(): void
    {
        $access = $this->access();
        $access->attempt('1234');
        $this->assertTrue($access->isAuthenticated());

        $access->logout();

        $this->assertFalse($access->isAuthenticated());
    }

    public function testChangingPropertyCodeRevokesExistingSession(): void
    {
        $access = $this->access();
        $this->assertTrue($access->attempt('1234'));

        $this->settingsService->setPropertyCodeHash(password_hash('5678', PASSWORD_DEFAULT));

        $this->assertFalse($access->isAuthenticated());
    }

    private function access(): ResidentAccess
    {
        return new ResidentAccess($this->settingsService, $this->rateLimiter, '192.0.2.20');
    }
}
