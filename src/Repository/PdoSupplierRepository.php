<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Supplier;
use PDO;

final class PdoSupplierRepository implements SupplierRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    /** @return list<Supplier> */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, contact_person, phone, email, address FROM suppliers ORDER BY id ASC');
        $stmt->execute();
        /** @var list<array{id: int|string, code: string, name: string, contact_person: string|null, phone: string|null, email: string|null, address: string|null}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $suppliers = [];
        foreach ($rows as $row) {
            $suppliers[] = new Supplier(
                (int) $row['id'],
                $row['code'],
                $row['name'],
                $row['contact_person'],
                $row['phone'],
                $row['email'],
                $row['address']
            );
        }

        return $suppliers;
    }

    public function findById(int $id): ?Supplier
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, contact_person, phone, email, address FROM suppliers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        /** @var array{id: int|string, code: string, name: string, contact_person: string|null, phone: string|null, email: string|null, address: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Supplier(
            (int) $row['id'],
            $row['code'],
            $row['name'],
            $row['contact_person'],
            $row['phone'],
            $row['email'],
            $row['address']
        );
    }

    public function findByCode(string $code, ?int $excludeId = null): ?Supplier
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare('SELECT id, code, name, contact_person, phone, email, address FROM suppliers WHERE code = ? AND id != ? LIMIT 1');
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id, code, name, contact_person, phone, email, address FROM suppliers WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
        }

        /** @var array{id: int|string, code: string, name: string, contact_person: string|null, phone: string|null, email: string|null, address: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Supplier(
            (int) $row['id'],
            $row['code'],
            $row['name'],
            $row['contact_person'],
            $row['phone'],
            $row['email'],
            $row['address']
        );
    }

    public function create(Supplier $supplier): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO suppliers (code, name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $supplier->code,
            $supplier->name,
            $supplier->contactPerson ?: null,
            $supplier->phone ?: null,
            $supplier->email ?: null,
            $supplier->address ?: null
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(Supplier $supplier): void
    {
        $stmt = $this->pdo->prepare('UPDATE suppliers SET code = ?, name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE id = ?');
        $stmt->execute([
            $supplier->code,
            $supplier->name,
            $supplier->contactPerson ?: null,
            $supplier->phone ?: null,
            $supplier->email ?: null,
            $supplier->address ?: null,
            $supplier->id
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getPurchaseOrderCount(int $supplierId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = ?');
        $stmt->execute([$supplierId]);
        return (int) $stmt->fetchColumn();
    }
}
