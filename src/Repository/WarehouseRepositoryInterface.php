<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Warehouse;

interface WarehouseRepositoryInterface
{
    /** @return list<Warehouse> */
    public function findAll(): array;
    public function findById(int $id): ?Warehouse;
    public function findByCode(string $code, ?int $excludeId = null): ?Warehouse;
    public function create(Warehouse $warehouse): int;
    public function update(Warehouse $warehouse): void;
    public function delete(int $id): void;
    public function getPhysicalStock(int $warehouseId): int;
    public function getOrderCount(int $warehouseId): int;
    /** @return list<int> */
    public function getAllWarehouseIds(): array;
    public function initWarehouseStockForProducts(int $warehouseId): void;
    public function deleteStocksAndLedger(int $warehouseId): void;
}
