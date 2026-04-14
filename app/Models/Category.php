<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Category
{
    public static function all(): array
    {
        $sql = 'SELECT c.*, i.filename AS cover_filename, COUNT(ic.image_id) AS images_count
                FROM categories c
                LEFT JOIN images i ON i.id = c.cover_image_id
                LEFT JOIN image_category ic ON ic.category_id = c.id
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.id DESC';
        return Database::instance()->pdo()->query($sql)->fetchAll() ?: [];
    }

    public static function visible(): array
    {
        $sql = 'SELECT c.*, i.id AS cover_image_ref, i.filename AS cover_filename
                FROM categories c
                LEFT JOIN images i ON i.id = c.cover_image_id
                WHERE c.is_visible = 1
                ORDER BY c.sort_order ASC, c.id DESC';
        return Database::instance()->pdo()->query($sql)->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $statement = Database::instance()->pdo()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $statement = Database::instance()->pdo()->prepare('SELECT * FROM categories WHERE slug = :slug AND is_visible = 1 LIMIT 1');
        $statement->execute([':slug' => $slug]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public static function create(array $data): bool
    {
        $sql = 'INSERT INTO categories (name_es, name_en, slug, is_visible, sort_order)
                VALUES (:name_es, :name_en, :slug, :is_visible, :sort_order)';
        $statement = Database::instance()->pdo()->prepare($sql);

        return $statement->execute([
            ':name_es' => $data['name_es'],
            ':name_en' => $data['name_en'],
            ':slug' => $data['slug'],
            ':is_visible' => !empty($data['is_visible']) ? 1 : 0,
            ':sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public static function update(int $id, array $data): bool
    {
        $sql = 'UPDATE categories
                SET name_es = :name_es, name_en = :name_en, slug = :slug, is_visible = :is_visible, cover_image_id = :cover_image_id
                WHERE id = :id';
        $statement = Database::instance()->pdo()->prepare($sql);

        return $statement->execute([
            ':id' => $id,
            ':name_es' => $data['name_es'],
            ':name_en' => $data['name_en'],
            ':slug' => $data['slug'],
            ':is_visible' => !empty($data['is_visible']) ? 1 : 0,
            ':cover_image_id' => $data['cover_image_id'] ?: null,
        ]);
    }

    public static function delete(int $id): bool
    {
        $statement = Database::instance()->pdo()->prepare('DELETE FROM categories WHERE id = :id');
        return $statement->execute([':id' => $id]);
    }

    public static function reorder(array $ids): void
    {
        $pdo = Database::instance()->pdo();
        $statement = $pdo->prepare('UPDATE categories SET sort_order = :sort_order WHERE id = :id');

        foreach ($ids as $index => $id) {
            $statement->execute([
                ':sort_order' => $index,
                ':id' => (int) $id,
            ]);
        }
    }

    public static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM categories WHERE slug = :slug';
        $params = [':slug' => $slug];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $statement = Database::instance()->pdo()->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetch();
    }

    public static function countAll(): int
    {
        $value = Database::instance()->pdo()->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        return (int) ($value ?: 0);
    }
}
