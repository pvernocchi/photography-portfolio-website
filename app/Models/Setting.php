<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Setting
{
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::getAll();
        return $all[$key]['setting_value'] ?? $default;
    }

    public static function set(string $key, mixed $value): bool
    {
        $statement = Database::instance()->pdo()->prepare(
            'INSERT INTO settings (setting_key, setting_value, setting_type, setting_group) 
             VALUES (:setting_key, :setting_value, \'text\', \'general\')
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP'
        );
        $result = $statement->execute([
            ':setting_key' => $key,
            ':setting_value' => $value,
        ]);

        self::$cache = null;
        return $result;
    }

    public static function getGroup(string $group): array
    {
        $all = self::getAll();
        return array_filter($all, static fn (array $setting): bool => $setting['setting_group'] === $group);
    }

    public static function getAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $rows = Database::instance()->pdo()->query('SELECT * FROM settings ORDER BY setting_group ASC, id ASC')->fetchAll() ?: [];
        self::$cache = [];
        foreach ($rows as $row) {
            self::$cache[$row['setting_key']] = $row;
        }

        return self::$cache;
    }
}
