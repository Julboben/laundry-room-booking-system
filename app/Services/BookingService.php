<?php

declare(strict_types=1);

namespace LaundryBooking\Services;

use DateTimeImmutable;
use LaundryBooking\Models\ActivityLog;
use LaundryBooking\Models\Booking;
use LaundryBooking\Support\DateHelper;
use LaundryBooking\Support\I18n;
use LaundryBooking\Support\Validator;
use PDO;
use PDOException;
use Throwable;

/**
 * Result of a booking-creation attempt.
 */
final class BookingCreationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $bookingId = null,
        public readonly ?string $plainCode = null,
        public readonly ?string $error = null,
    ) {
    }
}

final class BookingCancellationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
    ) {
    }
}

/**
 * Core domain logic for creating, listing, and cancelling bookings.
 * Slot start/end times are always derived from the canonical
 * definition in {@see self::slots()} and are never trusted from
 * request data.
 */
final class BookingService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CodeService $codeService,
        private readonly SettingsService $settings,
        private readonly ActivityLog $activityLog,
    ) {
    }

    /**
     * @return array<string,array{start:string,end:string}>
     */
    public static function slots(): array
    {
        return [
            '07-10' => ['start' => '07:00', 'end' => '10:00'],
            '10-13' => ['start' => '10:00', 'end' => '13:00'],
            '13-16' => ['start' => '13:00', 'end' => '16:00'],
            '16-19' => ['start' => '16:00', 'end' => '19:00'],
        ];
    }

    /**
     * Validate the requested date and slot, returning a localized error
     * message on failure or null on success.
     */
    public function validateDateAndSlot(string $date, string $slotKey): ?string
    {
        if (!DateHelper::isValidDateString($date)) {
            return I18n::translate('Datoen er ikke gyldig.');
        }

        if (!array_key_exists($slotKey, self::slots())) {
            return I18n::translate('Tiden er ikke gyldig.');
        }

        $today = DateHelper::today();
        $requestedDate = DateHelper::fromDateString($date);

        if ($requestedDate < $today) {
            return I18n::translate('Du kan ikke booke en tid i fortiden.');
        }

        $weeksAhead = $this->settings->getBookingWeeksAhead();
        $latestDate = $today->modify('+' . $weeksAhead . ' weeks');

        if ($requestedDate > $latestDate) {
            return I18n::translate('Datoen ligger for langt ude i fremtiden.');
        }

        $slot = self::slots()[$slotKey];
        $slotStart = DateHelper::fromDateString($date . ' ' . $slot['start']);

        if ($slotStart <= DateHelper::now()) {
            return I18n::translate('Denne tid er allerede startet.');
        }

        return null;
    }

    public function isAvailable(string $date, string $slotKey): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM bookings WHERE booking_date = :date AND slot_key = :slot_key'
        );
        $statement->execute(['date' => $date, 'slot_key' => $slotKey]);

        return ((int) $statement->fetchColumn()) === 0;
    }

    /**
     * @return array<string,Booking> keyed by "date|slotKey"
     */
    public function bookingsForRange(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, booking_date, slot_key, start_time, end_time, booking_name, created_at
             FROM bookings
             WHERE booking_date BETWEEN :start AND :end'
        );
        $statement->execute([
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ]);

        $bookings = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $booking = Booking::fromRow($row);
            $bookings[$booking->bookingDate . '|' . $booking->slotKey] = $booking;
        }

        return $bookings;
    }

    public function create(
        string $date,
        string $slotKey,
        string $name,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): BookingCreationResult {
        $nameError = Validator::validateName($name);
        if ($nameError !== null) {
            return new BookingCreationResult(false, error: $nameError);
        }

        $dateSlotError = $this->validateDateAndSlot($date, $slotKey);
        if ($dateSlotError !== null) {
            return new BookingCreationResult(false, error: $dateSlotError);
        }

        if (!$this->isAvailable($date, $slotKey)) {
            return new BookingCreationResult(
                false,
                error: I18n::translate('Tiden er desværre allerede booket.')
            );
        }

        $slot = self::slots()[$slotKey];
        $normalizedName = Validator::normalizeName($name);
        $plainCode = $this->codeService->generateCancellationCode($this->settings->getCancellationCodeLength());
        $codeHash = $this->codeService->hashCode($plainCode);

        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO bookings (booking_date, slot_key, start_time, end_time, booking_name, cancellation_code_hash)
                 VALUES (:booking_date, :slot_key, :start_time, :end_time, :booking_name, :cancellation_code_hash)'
            );
            $statement->execute([
                'booking_date' => $date,
                'slot_key' => $slotKey,
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
                'booking_name' => $normalizedName,
                'cancellation_code_hash' => $codeHash,
            ]);

            $bookingId = (int) $this->pdo->lastInsertId();

            $this->activityLog->log(
                actorType: 'resident',
                action: 'booking_created',
                bookingId: $bookingId,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                details: sprintf('%s %s', $date, $slotKey)
            );

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                return new BookingCreationResult(
                    false,
                    error: I18n::translate('Tiden er desværre allerede booket.')
                );
            }

            throw $exception;
        }

        return new BookingCreationResult(true, bookingId: $bookingId, plainCode: $plainCode);
    }

    public function find(int $bookingId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, booking_date, slot_key, start_time, end_time, booking_name, cancellation_code_hash, created_at
             FROM bookings WHERE id = :id'
        );
        $statement->execute(['id' => $bookingId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function cancel(
        int $bookingId,
        string $code,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): BookingCancellationResult {
        $formatError = Validator::validateCancellationCodeFormat(
                    $code,
                    $this->settings->getCancellationCodeLength()
                );
        if ($formatError !== null) {
            return new BookingCancellationResult(false, error: $formatError);
        }

        $this->pdo->beginTransaction();

        try {
            $lockSuffix = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $statement = $this->pdo->prepare(
                'SELECT cancellation_code_hash FROM bookings WHERE id = :id' . $lockSuffix
            );
            $statement->execute(['id' => $bookingId]);
            $booking = $statement->fetch(PDO::FETCH_ASSOC);

            if ($booking === false) {
                $this->pdo->rollBack();
                return new BookingCancellationResult(
                    false,
                    error: I18n::translate('Bookingen findes ikke.')
                );
            }

            if (!$this->codeService->verifyCode($code, $booking['cancellation_code_hash'])) {
                $this->pdo->rollBack();
                $this->activityLog->log(
                    actorType: 'resident',
                    action: 'booking_cancel_failed',
                    bookingId: $bookingId,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                );

                return new BookingCancellationResult(
                    false,
                    error: I18n::translate('Aflysningskoden er ikke korrekt.')
                );
            }

            $statement = $this->pdo->prepare('DELETE FROM bookings WHERE id = :id');
            $statement->execute(['id' => $bookingId]);

            $this->activityLog->log(
                actorType: 'resident',
                action: 'booking_cancelled',
                bookingId: $bookingId,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        return new BookingCancellationResult(true);
    }
}
