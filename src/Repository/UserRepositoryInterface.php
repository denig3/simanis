<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function emailExists(string $email, ?int $excludeId = null): bool;
    /** @return list<User> */
    public function findAll(): array;
    public function create(string $name, string $email, string $passwordHash, string $role = 'sales', ?int $assignedWarehouseId = null, string $status = 'active'): int;
    public function update(int $id, string $name, string $email, string $role, ?int $assignedWarehouseId, string $status, ?string $passwordHash = null): void;
    public function findFallbackUserId(string $role, int $excludeId): ?int;
    public function reassignUserRelations(int $targetId, int $fallbackSalesId, int $fallbackWarehouseId, int $currentUserId): void;
    public function delete(int $id): void;
}
