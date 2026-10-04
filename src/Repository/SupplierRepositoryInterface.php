<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Supplier;

interface SupplierRepositoryInterface
{
    /** @return list<Supplier> */
    public function findAll(): array;
    public function findById(int $id): ?Supplier;
    public function findByCode(string $code, ?int $excludeId = null): ?Supplier;
    public function create(Supplier $supplier): int;
    public function update(Supplier $supplier): void;
    public function delete(int $id): void;
    public function getPurchaseOrderCount(int $supplierId): int;
}
