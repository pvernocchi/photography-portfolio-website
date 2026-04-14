<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Image
{
    public static function all(): array
    {
        return Database::instance()->pdo()->query('SELECT * FROM images ORDER BY id DESC')->fetchAll() ?: [];
    }

    public static function byCategory(int $categoryId): array
    {
        $sql = 'SELECT i.* FROM images i
                INNER JOIN image_category ic ON ic.image_id = i.id
                WHERE ic.category_id = :category_id
                ORDER BY ic.sort_order ASC, i.id DESC';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute([':category_id' => $categoryId]);
        return $statement->fetchAll() ?: [];
    }

    public static function unassigned(): array
    {
        $sql = 'SELECT i.* FROM images i
                LEFT JOIN image_category ic ON ic.image_id = i.id
                WHERE ic.image_id IS NULL
                ORDER BY i.id DESC';
        return Database::instance()->pdo()->query($sql)->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $statement = Database::instance()->pdo()->prepare('SELECT * FROM images WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::instance()->pdo();
        $sql = 'INSERT INTO images (filename, original_filename, width, height, file_size)
                VALUES (:filename, :original_filename, :width, :height, :file_size)';
        $statement = $pdo->prepare($sql);

        $statement->execute([
            ':filename' => $data['filename'],
            ':original_filename' => $data['original_filename'],
            ':width' => $data['width'],
            ':height' => $data['height'],
            ':file_size' => $data['file_size'],
        ]);

        return (int) $pdo->lastInsertId();
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

    /** @param int[] $ids */
    public static function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $ids = array_slice($ids, 0, 500);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = Database::instance()->pdo()->prepare("SELECT * FROM images WHERE id IN ($placeholders)");
        $statement->execute(array_map('intval', $ids));
        return $statement->fetchAll() ?: [];
    }

    /** @param int[] $ids */
    public static function deleteMany(array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $ids = array_slice($ids, 0, 500);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        Database::instance()->pdo()
            ->prepare("DELETE FROM images WHERE id IN ($placeholders)")
            ->execute(array_map('intval', $ids));
    }

    /** @param int[] $imageIds */
    public static function removeFromCategory(int $categoryId, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }
        $imageIds = array_slice($imageIds, 0, 500);
        $placeholders = implode(',', array_fill(0, count($imageIds), '?'));
        $params = array_merge([$categoryId], array_map('intval', $imageIds));
        Database::instance()->pdo()
            ->prepare("DELETE FROM image_category WHERE category_id = ? AND image_id IN ($placeholders)")
            ->execute($params);
    }

    public static function reorder(int $categoryId, array $ids): void
    {
        $pdo = Database::instance()->pdo();
        $sql = 'UPDATE image_category SET sort_order = :sort_order WHERE image_id = :image_id AND category_id = :category_id';
        $statement = $pdo->prepare($sql);

        foreach ($ids as $index => $id) {
            $statement->execute([
                ':sort_order' => $index,
                ':image_id' => (int) $id,
                ':category_id' => $categoryId,
            ]);
        }
    }

    public static function assignCategories(int $imageId, array $categoryIds): void
    {
        $pdo = Database::instance()->pdo();

        $pdo->prepare('DELETE FROM image_category WHERE image_id = :image_id')
            ->execute([':image_id' => $imageId]);

        if ($categoryIds === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $sortStmt = $pdo->prepare(
            'SELECT category_id, COALESCE(MAX(sort_order), 0) + 1 AS next_sort
             FROM image_category WHERE category_id IN (' . $placeholders . ') GROUP BY category_id'
        );
        $sortStmt->execute(array_map('intval', $categoryIds));
        $sortMap = [];
        foreach ($sortStmt->fetchAll() as $row) {
            $sortMap[(int) $row['category_id']] = (int) $row['next_sort'];
        }

        $sql = 'INSERT INTO image_category (image_id, category_id, sort_order)
                VALUES (:image_id, :category_id, :sort_order)';
        $statement = $pdo->prepare($sql);

        foreach ($categoryIds as $categoryId) {
            $statement->execute([
                ':image_id' => $imageId,
                ':category_id' => (int) $categoryId,
                ':sort_order' => $sortMap[(int) $categoryId] ?? 0,
            ]);
        }
    }

    /** @param int[] $imageIds */
    public static function addToCategory(int $categoryId, array $imageIds): int
    {
        if ($categoryId < 1 || $imageIds === []) {
            return 0;
        }

        $imageIds = array_values(array_unique(array_filter(array_map('intval', $imageIds), static fn (int $id) => $id > 0)));
        if ($imageIds === []) {
            return 0;
        }
        $imageIds = array_slice($imageIds, 0, 500);

        $pdo = Database::instance()->pdo();
        $placeholders = implode(',', array_fill(0, count($imageIds), '?'));
        $existingStmt = $pdo->prepare(
            "SELECT image_id FROM image_category WHERE category_id = ? AND image_id IN ($placeholders)"
        );
        $existingStmt->execute(array_merge([$categoryId], $imageIds));
        $existingIds = array_flip(array_map('intval', array_column($existingStmt->fetchAll() ?: [], 'image_id')));

        $maxSortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM image_category WHERE category_id = :category_id');
        $maxSortStmt->execute([':category_id' => $categoryId]);
        $nextSort = (int) ($maxSortStmt->fetchColumn() ?: 1);

        $insertStmt = $pdo->prepare(
            'INSERT INTO image_category (image_id, category_id, sort_order) VALUES (:image_id, :category_id, :sort_order)'
        );

        $assigned = 0;
        foreach ($imageIds as $imageId) {
            if (isset($existingIds[$imageId])) {
                continue;
            }
            $insertStmt->execute([
                ':image_id' => $imageId,
                ':category_id' => $categoryId,
                ':sort_order' => $nextSort++,
            ]);
            $assigned++;
        }

        return $assigned;
    }

    public static function categoriesForImage(int $imageId): array
    {
        $sql = 'SELECT c.* FROM categories c
                INNER JOIN image_category ic ON ic.category_id = c.id
                WHERE ic.image_id = :image_id
                ORDER BY c.sort_order ASC';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute([':image_id' => $imageId]);
        return $statement->fetchAll() ?: [];
    }

    public static function categoryIdsForImage(int $imageId): array
    {
        $sql = 'SELECT category_id FROM image_category WHERE image_id = :image_id';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute([':image_id' => $imageId]);
        return array_column($statement->fetchAll() ?: [], 'category_id');
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
