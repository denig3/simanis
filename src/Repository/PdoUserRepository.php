<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\User;
use PDO;

final class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT u.id, u.name, u.email, u.password_hash, u.role, u.assigned_warehouse_id, u.status, w.name AS warehouse_name
            FROM users u
            LEFT JOIN warehouses w ON u.assigned_warehouse_id = w.id
            WHERE u.id = ?
            LIMIT 1
        ');
        $stmt->execute([$id]);
        /** @var array{id: int|string, name: string, email: string, password_hash: string, role: string, assigned_warehouse_id: int|string|null, status: string, warehouse_name: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new User(
            (int) $row['id'],
            $row['name'],
            $row['email'],
            $row['password_hash'],
            $row['role'],
            $row['assigned_warehouse_id'] !== null ? (int) $row['assigned_warehouse_id'] : null,
            $row['status'],
            $row['warehouse_name'] ?? null
        );
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT u.id, u.name, u.email, u.password_hash, u.role, u.assigned_warehouse_id, u.status, w.name AS warehouse_name
            FROM users u
            LEFT JOIN warehouses w ON u.assigned_warehouse_id = w.id
            WHERE u.email = ?
            LIMIT 1
        ');
        $stmt->execute([$email]);
        /** @var array{id: int|string, name: string, email: string, password_hash: string, role: string, assigned_warehouse_id: int|string|null, status: string, warehouse_name: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new User(
            (int) $row['id'],
            $row['name'],
            $row['email'],
            $row['password_hash'],
            $row['role'],
            $row['assigned_warehouse_id'] !== null ? (int) $row['assigned_warehouse_id'] : null,
            $row['status'],
            $row['warehouse_name'] ?? null
        );
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
        }

        return $stmt->fetch() !== false;
    }

    /** @return list<User> */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT u.id, u.name, u.email, u.password_hash, u.role, u.assigned_warehouse_id, u.status, w.name AS warehouse_name
            FROM users u
            LEFT JOIN warehouses w ON u.assigned_warehouse_id = w.id
            ORDER BY u.id ASC
        ');
        $stmt->execute();
        /** @var list<array{id: int|string, name: string, email: string, password_hash: string, role: string, assigned_warehouse_id: int|string|null, status: string, warehouse_name: string|null}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $users = [];
        foreach ($rows as $row) {
            $users[] = new User(
                (int) $row['id'],
                $row['name'],
                $row['email'],
                $row['password_hash'],
                $row['role'],
                $row['assigned_warehouse_id'] !== null ? (int) $row['assigned_warehouse_id'] : null,
                $row['status'],
                $row['warehouse_name'] ?? null
            );
        }

        return $users;
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        string $role = 'sales',
        ?int $assignedWarehouseId = null,
        string $status = 'active'
    ): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (name, email, password_hash, role, assigned_warehouse_id, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$name, $email, $passwordHash, $role, $assignedWarehouseId, $status]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(
        int $id,
        string $name,
        string $email,
        string $role,
        ?int $assignedWarehouseId,
        string $status,
        ?string $passwordHash = null
    ): void {
        if ($passwordHash !== null && $passwordHash !== '') {
            $stmt = $this->pdo->prepare('
                UPDATE users
                SET name = ?, email = ?, password_hash = ?, role = ?, assigned_warehouse_id = ?, status = ?
                WHERE id = ?
            ');
            $stmt->execute([$name, $email, $passwordHash, $role, $assignedWarehouseId, $status, $id]);
        } else {
            $stmt = $this->pdo->prepare('
                UPDATE users
                SET name = ?, email = ?, role = ?, assigned_warehouse_id = ?, status = ?
                WHERE id = ?
            ');
            $stmt->execute([$name, $email, $role, $assignedWarehouseId, $status, $id]);
        }
    }

    public function findFallbackUserId(string $role, int $excludeId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE role = ? AND id != ? LIMIT 1');
        $stmt->execute([$role, $excludeId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }

    public function reassignUserRelations(int $targetId, int $fallbackSalesId, int $fallbackWarehouseId, int $currentUserId): void
    {
        $stmt1 = $this->pdo->prepare('UPDATE sales_orders SET created_by = ? WHERE created_by = ?');
        $stmt1->execute([$fallbackSalesId, $targetId]);

        $stmt2 = $this->pdo->prepare('UPDATE sales_orders SET approved_by = NULL WHERE approved_by = ?');
        $stmt2->execute([$targetId]);

        $stmt3 = $this->pdo->prepare('UPDATE purchase_orders SET created_by = ? WHERE created_by = ?');
        $stmt3->execute([$currentUserId, $targetId]);

        $stmt4 = $this->pdo->prepare('UPDATE purchase_orders SET approved_by = NULL WHERE approved_by = ?');
        $stmt4->execute([$targetId]);

        $stmt5 = $this->pdo->prepare('UPDATE goods_issues SET issued_by = ? WHERE issued_by = ?');
        $stmt5->execute([$fallbackWarehouseId, $targetId]);

        $stmt6 = $this->pdo->prepare('UPDATE goods_receipts SET received_by = ? WHERE received_by = ?');
        $stmt6->execute([$fallbackWarehouseId, $targetId]);

        $stmt7 = $this->pdo->prepare('UPDATE stock_ledger SET user_id = ? WHERE user_id = ?');
        $stmt7->execute([$currentUserId, $targetId]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}
