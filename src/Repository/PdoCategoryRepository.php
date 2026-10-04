<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Category;
use PDO;

final class PdoCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    /** @return list<Category> */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, description FROM categories ORDER BY id ASC');
        $stmt->execute();
        /** @var list<array{id: int|string, code: string, name: string, description: string|null}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = new Category(
                (int) $row['id'],
                $row['code'],
                $row['name'],
                $row['description']
            );
        }

        return $categories;
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, description FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        /** @var array{id: int|string, code: string, name: string, description: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Category(
            (int) $row['id'],
            $row['code'],
            $row['name'],
            $row['description']
        );
    }

    public function findByCode(string $code, ?int $excludeId = null): ?Category
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare('SELECT id, code, name, description FROM categories WHERE code = ? AND id != ? LIMIT 1');
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id, code, name, description FROM categories WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
        }

        /** @var array{id: int|string, code: string, name: string, description: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Category(
            (int) $row['id'],
            $row['code'],
            $row['name'],
            $row['description']
        );
    }

    public function create(Category $category): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO categories (code, name, description) VALUES (?, ?, ?)');
        $stmt->execute([$category->code, $category->name, $category->description ?: null]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(Category $category): void
    {
        $stmt = $this->pdo->prepare('UPDATE categories SET code = ?, name = ?, description = ? WHERE id = ?');
        $stmt->execute([$category->code, $category->name, $category->description ?: null, $category->id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getProductCount(int $categoryId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->execute([$categoryId]);
        return (int) $stmt->fetchColumn();
    }
}
