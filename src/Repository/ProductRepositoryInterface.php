<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Product;

interface ProductRepositoryInterface
{
    /** @return list<Product> */
    public function findAllWithStock(): array;
    public function findById(int $id): ?Product;
    public function findBySku(string $sku, ?int $excludeId = null): ?Product;
    public function create(Product $product): int;
    public function update(Product $product): void;
    public function delete(int $id): void;
    /** @return array{so: int, po: int} */
    public function getTransactionCount(int $productId): array;
    public function getTotalStock(int $productId): int;
    public function deleteStocksAndLedger(int $productId): void;
    /** @param array<int, int> $warehouseStocks */
    public function initProductStocks(int $productId, array $warehouseStocks, int $userId, string $sku): void;
}
