<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Response;

try {
    $pdo = Connection::get();
    $pdo->query('SELECT 1');
    $databaseStatus = 'ok';
    $statusCode = 200;
    $status = 'ok';
} catch (\Throwable) {
    $databaseStatus = 'error';
    $statusCode = 500;
    $status = 'error';
}

Response::json([
    'status' => $status,
    'database' => $databaseStatus,
    'time' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Copenhagen')))->format(DATE_ATOM),
], $statusCode);
