<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Auth\ResidentAccess;
use LaundryBooking\Models\Setting;
use LaundryBooking\Services\SettingsService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ResidentAccessTest extends TestCase
{
    private SettingsService $settingsService;

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

        $settings = new Setting($pdo);
        $this->settingsService = new SettingsService($settings);
    }

    public function testCorrectCodeGrantsAccess(): void
    {
        $access = new ResidentAccess($this->settingsService);

        $this->assertTrue($access->attempt('1234'));
        $this->assertTrue($access->isAuthenticated());
    }

    public function testIncorrectCodeIsRejected(): void
    {
        $access = new ResidentAccess($this->settingsService);

        $this->assertFalse($access->attempt('0000'));
        $this->assertFalse($access->isAuthenticated());
    }

    public function testLocksOutAfterFiveFailedAttempts(): void
    {
        $access = new ResidentAccess($this->settingsService);

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($access->attempt('0000'));
        }

        $this->assertTrue($access->isLockedOut());
        $this->assertGreaterThan(0, $access->lockoutRemainingSeconds());
        $this->assertFalse($access->attempt('1234'));
    }

    public function testLogoutClearsAccess(): void
    {
        $access = new ResidentAccess($this->settingsService);
        $access->attempt('1234');
        $this->assertTrue($access->isAuthenticated());

        $access->logout();

        $this->assertFalse($access->isAuthenticated());
    }
}
