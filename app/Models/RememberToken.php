<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class RememberToken
{
    private const COOKIE_NAME = 'remember_token';

    public static function issue(int $userId, int $days): void
    {
        self::revokeByUser($userId);

        $token = bin2hex(random_bytes(64));
        $hash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + ($days * 86400));

        $sql = 'INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute([
            ':user_id' => $userId,
            ':token_hash' => $hash,
            ':expires_at' => $expiresAt,
        ]);

        setcookie(self::COOKIE_NAME, $token, [
            'expires' => strtotime($expiresAt),
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    public static function validateToken(string $plainToken): ?int
    {
        $hash = hash('sha256', $plainToken);

        $sql = 'SELECT user_id FROM remember_tokens WHERE token_hash = :token_hash AND expires_at > NOW() LIMIT 1';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute([':token_hash' => $hash]);
        $row = $statement->fetch();

        if (!$row) {
            self::revokePlainToken($plainToken);
            return null;
        }

        return (int) $row['user_id'];
    }

    public static function revokePlainToken(string $plainToken): void
    {
        $hash = hash('sha256', $plainToken);
        $sql = 'DELETE FROM remember_tokens WHERE token_hash = :token_hash';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute([':token_hash' => $hash]);
    }

    public static function revokeByUser(int $userId): void
    {
        $sql = 'DELETE FROM remember_tokens WHERE user_id = :user_id';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute([':user_id' => $userId]);
    }
}
