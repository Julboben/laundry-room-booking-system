<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use LaundryBooking\Database\Connection;
use LaundryBooking\Support\Env;

Env::load(__DIR__ . '/../.env');

$pdo = Connection::get();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$migrationsDir = __DIR__ . '/../app/Database/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

$appliedStatement = $pdo->query('SELECT migration FROM migrations');
$applied = $appliedStatement !== false ? $appliedStatement->fetchAll(PDO::FETCH_COLUMN) : [];

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        echo "Already applied: {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);

    if ($sql === false) {
        fwrite(STDERR, "Could not read migration: {$name}\n");
        exit(1);
    }

    try {
        // DDL statements implicitly commit in MySQL, so migrations are
        // not wrapped in an explicit transaction here.
        $pdo->exec($sql);

        $insert = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
        $insert->execute(['migration' => $name]);

        echo "Applied: {$name}\n";
    } catch (\Throwable $exception) {
        fwrite(STDERR, "Failed to apply {$name}: {$exception->getMessage()}\n");
        exit(1);
    }
}

echo "Migrations complete.\n";
