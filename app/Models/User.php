<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public static function findByUsername(string $username): ?array
    {
        $sql = 'SELECT * FROM users WHERE username = :username LIMIT 1';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute([':username' => $username]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM users WHERE id = :id LIMIT 1';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute([':id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function enableMfa(int $id, string $secret): bool
    {
        $sql = 'UPDATE users SET totp_secret = :totp_secret, mfa_enabled = 1 WHERE id = :id';
        $statement = Database::instance()->pdo()->prepare($sql);

        return $statement->execute([
            ':totp_secret' => $secret,
            ':id' => $id,
        ]);
    }
}
