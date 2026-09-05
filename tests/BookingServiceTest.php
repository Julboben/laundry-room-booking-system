<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Database\Connection;
use LaundryBooking\Models\ActivityLog;
use LaundryBooking\Models\Setting;
use LaundryBooking\Services\BookingService;
use LaundryBooking\Services\CodeService;
use LaundryBooking\Services\SettingsService;
use LaundryBooking\Support\DateHelper;
use LaundryBooking\Support\Env;
use PDO;
use PHPUnit\Framework\TestCase;

final class BookingServiceTest extends TestCase
{
    private PDO $pdo;

    private BookingService $bookingService;

    protected function setUp(): void
    {
        Env::load(__DIR__ . '/../.env');

        if (Env::get('DB_DATABASE') === null) {
            self::markTestSkipped('No test database configured.');
        }

        $this->pdo = Connection::create();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('TRUNCATE TABLE bookings');
        $this->pdo->exec('TRUNCATE TABLE activity_logs');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $settings = new Setting($this->pdo);
        $settings->set('booking_weeks_ahead', '8');

        $settingsService = new SettingsService($settings);
        $this->bookingService = new BookingService(
            $this->pdo,
            new CodeService(),
            $settingsService,
            new ActivityLog($this->pdo)
        );
    }

    private function futureDate(int $daysAhead = 3): string
    {
        return DateHelper::today()->modify("+{$daysAhead} days")->format('Y-m-d');
    }

    public function testSlotsAreCanonical(): void
    {
        $slots = BookingService::slots();

        $this->assertSame(
            ['07-10', '10-13', '13-16', '16-19'],
            array_keys($slots)
        );
        $this->assertSame('07:00', $slots['07-10']['start']);
        $this->assertSame('19:00', $slots['16-19']['end']);
    }

    public function testInvalidSlotIsRejected(): void
    {
        $error = $this->bookingService->validateDateAndSlot($this->futureDate(), 'not-a-slot');
        $this->assertSame('Tiden er ikke gyldig.', $error);
    }

    public function testPastDateIsRejected(): void
    {
        $pastDate = DateHelper::today()->modify('-1 day')->format('Y-m-d');
        $error = $this->bookingService->validateDateAndSlot($pastDate, '07-10');
        $this->assertSame('Du kan ikke booke en tid i fortiden.', $error);
    }

    public function testDateBeyondBookingWindowIsRejected(): void
    {
        $farDate = DateHelper::today()->modify('+20 weeks')->format('Y-m-d');
        $error = $this->bookingService->validateDateAndSlot($farDate, '07-10');
        $this->assertSame('Datoen ligger for langt ude i fremtiden.', $error);
    }

    public function testCreateBookingSucceeds(): void
    {
        $result = $this->bookingService->create($this->futureDate(), '07-10', 'Anna Hansen');

        $this->assertTrue($result->success);
        $this->assertNotNull($result->bookingId);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $result->plainCode);
    }

    public function testDoubleBookingIsPrevented(): void
    {
        $date = $this->futureDate();

        $first = $this->bookingService->create($date, '07-10', 'Anna Hansen');
        $this->assertTrue($first->success);

        $second = $this->bookingService->create($date, '07-10', 'Bo Nielsen');

        $this->assertFalse($second->success);
        $this->assertSame('Tiden er desværre allerede booket.', $second->error);
    }

    public function testInvalidNameIsRejected(): void
    {
        $result = $this->bookingService->create($this->futureDate(), '07-10', 'A');

        $this->assertFalse($result->success);
        $this->assertSame('Navnet er for kort.', $result->error);
    }

    public function testCancelWithCorrectCodeSucceeds(): void
    {
        $date = $this->futureDate();
        $created = $this->bookingService->create($date, '10-13', 'Carla Berg');
        $this->assertTrue($created->success);

        $result = $this->bookingService->cancel($created->bookingId, $created->plainCode);

        $this->assertTrue($result->success);
        $this->assertNull($this->bookingService->find($created->bookingId));

        $reBooked = $this->bookingService->create($date, '10-13', 'Dan Poulsen');
        $this->assertTrue($reBooked->success);
    }

    public function testCancelWithIncorrectCodeFails(): void
    {
        $date = $this->futureDate();
        $created = $this->bookingService->create($date, '13-16', 'Eva Krogh');
        $this->assertTrue($created->success);

        $result = $this->bookingService->cancel($created->bookingId, '000000');

        $this->assertFalse($result->success);
        $this->assertSame('Aflysningskoden er ikke korrekt.', $result->error);
        $this->assertNotNull($this->bookingService->find($created->bookingId));
    }

    public function testCancelWithMalformedCodeIsRejected(): void
    {
        $date = $this->futureDate();
        $created = $this->bookingService->create($date, '16-19', 'Finn Aas');
        $this->assertTrue($created->success);

        $result = $this->bookingService->cancel($created->bookingId, 'abc');

        $this->assertFalse($result->success);
        $this->assertSame('Aflysningskoden skal være på 6 cifre.', $result->error);
    }
}
