<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Image
{
    public static function byCategory(int $categoryId): array
    {
        $statement = Database::instance()->pdo()->prepare('SELECT * FROM images WHERE category_id = :category_id ORDER BY sort_order ASC, id DESC');
        $statement->execute([':category_id' => $categoryId]);
        return $statement->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $statement = Database::instance()->pdo()->prepare('SELECT * FROM images WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public static function create(array $data): bool
    {
        $sql = 'INSERT INTO images (category_id, filename, original_filename, width, height, file_size, sort_order)
                VALUES (:category_id, :filename, :original_filename, :width, :height, :file_size, :sort_order)';
        $statement = Database::instance()->pdo()->prepare($sql);

        return $statement->execute([
            ':category_id' => $data['category_id'],
            ':filename' => $data['filename'],
            ':original_filename' => $data['original_filename'],
            ':width' => $data['width'],
            ':height' => $data['height'],
            ':file_size' => $data['file_size'],
            ':sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public static function updateMeta(int $id, array $data): bool
    {
        $sql = 'UPDATE images SET title_es = :title_es, title_en = :title_en, alt_es = :alt_es, alt_en = :alt_en WHERE id = :id';
        $statement = Database::instance()->pdo()->prepare($sql);

        return $statement->execute([
            ':id' => $id,
            ':title_es' => $data['title_es'] ?: null,
            ':title_en' => $data['title_en'] ?: null,
            ':alt_es' => $data['alt_es'] ?: null,
            ':alt_en' => $data['alt_en'] ?: null,
        ]);
    }

    public static function delete(int $id): bool
    {
        $statement = Database::instance()->pdo()->prepare('DELETE FROM images WHERE id = :id');
        return $statement->execute([':id' => $id]);
    }

    public static function reorder(int $categoryId, array $ids): void
    {
        $pdo = Database::instance()->pdo();
        $statement = $pdo->prepare('UPDATE images SET sort_order = :sort_order WHERE id = :id AND category_id = :category_id');

        foreach ($ids as $index => $id) {
            $statement->execute([
                ':sort_order' => $index,
                ':id' => (int) $id,
                ':category_id' => $categoryId,
            ]);
        }
    }

    public static function countAll(): int
    {
        $value = Database::instance()->pdo()->query('SELECT COUNT(*) FROM images')->fetchColumn();
        return (int) ($value ?: 0);
    }

    public static function storageBytes(): int
    {
        $value = Database::instance()->pdo()->query('SELECT COALESCE(SUM(file_size), 0) FROM images')->fetchColumn();
        return (int) ($value ?: 0);
    }
}
