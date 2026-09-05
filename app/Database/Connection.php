<?php

declare(strict_types=1);

namespace LaundryBooking\Database;

use LaundryBooking\Support\Env;
use PDO;
use PDOException;

/**
 * Provides a shared PDO connection using prepared-statement-friendly
 * defaults. Connection details are read from the environment.
 */
final class Connection
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        self::$instance = self::create();

        return self::$instance;
    }

    public static function create(): PDO
    {
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_DATABASE', 'laundry_booking');
        $username = Env::get('DB_USERNAME', 'root');
        $password = Env::get('DB_PASSWORD', '') ?? '';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new PDOException('Database connection failed.', (int) $exception->getCode(), $exception);
        }
    }

    /**
     * Reset the cached connection. Useful for tests.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function set(PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
