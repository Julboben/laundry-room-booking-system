<?php

declare(strict_types=1);

namespace LaundryBooking\Security;

use PDO;
use Throwable;

/**
 * Database-backed failed-attempt limiter shared across sessions and app instances.
 */
final class RateLimiter
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function isLocked(string $scope, string $subject): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT locked_until FROM rate_limits WHERE scope = :scope AND subject_hash = :subject_hash'
        );
        $statement->execute([
            'scope' => $scope,
            'subject_hash' => $this->hashSubject($subject),
        ]);
        $lockedUntil = $statement->fetchColumn();

        return $lockedUntil !== false && (int) $lockedUntil > time();
    }

    public function remainingSeconds(string $scope, string $subject): int
    {
        $statement = $this->pdo->prepare(
            'SELECT locked_until FROM rate_limits WHERE scope = :scope AND subject_hash = :subject_hash'
        );
        $statement->execute([
            'scope' => $scope,
            'subject_hash' => $this->hashSubject($subject),
        ]);
        $lockedUntil = $statement->fetchColumn();

        return $lockedUntil === false ? 0 : max(0, (int) $lockedUntil - time());
    }

    public function recordFailure(
        string $scope,
        string $subject,
        int $maxAttempts,
        int $windowSeconds,
        int $lockoutSeconds
    ): void {
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $subjectHash = $this->hashSubject($subject);
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $seedSql = $driver === 'sqlite'
                ? 'INSERT INTO rate_limits
                       (scope, subject_hash, failed_attempts, window_started_at, locked_until)
                   VALUES (:scope, :subject_hash, 0, :window_started_at, 0)
                   ON CONFLICT(scope, subject_hash) DO NOTHING'
                : 'INSERT INTO rate_limits
                       (scope, subject_hash, failed_attempts, window_started_at, locked_until)
                   VALUES (:scope, :subject_hash, 0, :window_started_at, 0)
                   ON DUPLICATE KEY UPDATE failed_attempts = failed_attempts';
            $seed = $this->pdo->prepare($seedSql);
            $seed->execute([
                'scope' => $scope,
                'subject_hash' => $subjectHash,
                'window_started_at' => time(),
            ]);

            $lockSuffix = $driver === 'mysql' ? ' FOR UPDATE' : '';
            $statement = $this->pdo->prepare(
                'SELECT failed_attempts, window_started_at, locked_until
                 FROM rate_limits
                 WHERE scope = :scope AND subject_hash = :subject_hash' . $lockSuffix
            );
            $statement->execute([
                'scope' => $scope,
                'subject_hash' => $subjectHash,
            ]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            $now = time();

            if ($row === false) {
                throw new \RuntimeException('Could not initialize the rate-limit record.');
            }

            if ((int) $row['window_started_at'] <= $now - $windowSeconds) {
                $attempts = 1;
                $windowStartedAt = $now;
            } else {
                $attempts = (int) $row['failed_attempts'] + 1;
                $windowStartedAt = (int) $row['window_started_at'];
            }

            $lockedUntil = $attempts >= $maxAttempts ? $now + $lockoutSeconds : 0;

            $write = $this->pdo->prepare(
                'UPDATE rate_limits
                 SET failed_attempts = :failed_attempts,
                     window_started_at = :window_started_at,
                     locked_until = :locked_until
                 WHERE scope = :scope AND subject_hash = :subject_hash'
            );

            $write->execute([
                'scope' => $scope,
                'subject_hash' => $subjectHash,
                'failed_attempts' => $attempts,
                'window_started_at' => $windowStartedAt,
                'locked_until' => $lockedUntil,
            ]);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function clear(string $scope, string $subject): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM rate_limits WHERE scope = :scope AND subject_hash = :subject_hash'
        );
        $statement->execute([
            'scope' => $scope,
            'subject_hash' => $this->hashSubject($subject),
        ]);
    }

    private function hashSubject(string $subject): string
    {
        return hash('sha256', $subject);
    }
}
