<?php

declare(strict_types=1);

/**
 * Dumps the application tables to a timestamped SQL file in
 * storage/backups and deletes backups older than
 * BACKUP_RETENTION_DAYS.
 *
 * If the `mysqldump` binary is not available on the hosting
 * environment, use the control panel's own database backup/export
 * tool instead (for example phpMyAdmin's "Export" feature on
 * Simply.com), and adjust BACKUP_RETENTION_DAYS-based cleanup
 * manually through the panel.
 */

require __DIR__ . '/../vendor/autoload.php';

use LaundryBooking\Support\Env;

Env::load(__DIR__ . '/../.env');

$backupDir = __DIR__ . '/../storage/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$host = Env::get('DB_HOST', '127.0.0.1');
$port = Env::get('DB_PORT', '3306');
$database = Env::get('DB_DATABASE', 'laundry_booking');
$username = Env::get('DB_USERNAME', 'root');
$password = Env::get('DB_PASSWORD', '') ?? '';

$timestamp = (new DateTimeImmutable('now', new DateTimeZone(Env::get('APP_TIMEZONE', 'Europe/Copenhagen') ?? 'Europe/Copenhagen')))
    ->format('Ymd_His');
$outputFile = $backupDir . "/backup_{$timestamp}.sql";

$mysqldumpAvailable = trim((string) shell_exec('command -v mysqldump 2>/dev/null')) !== '';

if (!$mysqldumpAvailable) {
    fwrite(
        STDERR,
        "mysqldump is not available. Use your hosting control panel's backup/export tool " .
        "(e.g. phpMyAdmin export) instead, and manage retention manually.\n"
    );
    exit(1);
}

$command = sprintf(
    'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
    escapeshellarg($host),
    escapeshellarg((string) $port),
    escapeshellarg($username),
    $password !== '' ? '--password=' . escapeshellarg($password) : '',
    escapeshellarg($database),
    escapeshellarg($outputFile)
);

exec($command, $outputLines, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "Backup failed with exit code {$exitCode}.\n");
    exit(1);
}

echo "Backup written to {$outputFile}\n";

$retentionDays = Env::getInt('BACKUP_RETENTION_DAYS', 30);
$cutoff = time() - ($retentionDays * 86400);

foreach (glob($backupDir . '/backup_*.sql') as $file) {
    if (filemtime($file) < $cutoff) {
        unlink($file);
        echo "Removed old backup: {$file}\n";
    }
}
