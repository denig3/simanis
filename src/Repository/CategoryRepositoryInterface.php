<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Category;

interface CategoryRepositoryInterface
{
    /** @return list<Category> */
    public function findAll(): array;
    public function findById(int $id): ?Category;
    public function findByCode(string $code, ?int $excludeId = null): ?Category;
    public function create(Category $category): int;
    public function update(Category $category): void;
    public function delete(int $id): void;
    public function getProductCount(int $categoryId): int;
}
