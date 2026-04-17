<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class WebAuthnCredential
{
    public static function findByCredentialId(string $credentialId): ?array
    {
        $sql = 'SELECT * FROM webauthn_credentials WHERE credential_id = :cid LIMIT 1';
        $stmt = Database::instance()->pdo()->prepare($sql);
        $stmt->execute([':cid' => $credentialId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function findByUserId(int $userId): array
    {
        $sql  = 'SELECT * FROM webauthn_credentials WHERE user_id = :uid ORDER BY created_at ASC';
        $stmt = Database::instance()->pdo()->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function create(
        int    $userId,
        string $credentialId,
        string $publicKeyPem,
        string $name
    ): int {
        $sql = 'INSERT INTO webauthn_credentials (user_id, credential_id, public_key_pem, sign_count, name)
                VALUES (:uid, :cid, :pem, 0, :name)';
        $stmt = Database::instance()->pdo()->prepare($sql);
        $stmt->execute([
            ':uid'  => $userId,
            ':cid'  => $credentialId,
            ':pem'  => $publicKeyPem,
            ':name' => $name,
        ]);
        return (int) Database::instance()->pdo()->lastInsertId();
    }

    public static function updateSignCount(int $id, int $newCount): bool
    {
        $sql  = 'UPDATE webauthn_credentials SET sign_count = :count WHERE id = :id';
        $stmt = Database::instance()->pdo()->prepare($sql);
        return $stmt->execute([':count' => $newCount, ':id' => $id]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $sql  = 'DELETE FROM webauthn_credentials WHERE id = :id AND user_id = :uid';
        $stmt = Database::instance()->pdo()->prepare($sql);
        return $stmt->execute([':id' => $id, ':uid' => $userId]) && $stmt->rowCount() > 0;
    }
}
