<?php

declare(strict_types=1);

namespace LaundryBooking\Models;

use PDO;

/**
 * Data access for the key/value settings table.
 */
final class Setting
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function get(string $key): ?string
    {
        $statement = $this->pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = :key');
        $statement->execute(['key' => $key]);
        $value = $statement->fetchColumn();

        return $value === false ? null : (string) $value;
    }

    public function set(string $key, string $value): void
    {
        $upsertClause = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? 'ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value'
            : 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)';
        $statement = $this->pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ' . $upsertClause
        );
        $statement->execute(['key' => $key, 'value' => $value]);
    }

    /**
     * @return array<string,string>
     */
    public function all(): array
    {
        $statement = $this->pdo->query('SELECT setting_key, setting_value FROM settings');
        $rows = $statement !== false ? $statement->fetchAll(PDO::FETCH_KEY_PAIR) : [];

        return $rows;
    }
}
