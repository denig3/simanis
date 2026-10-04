<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Customer;
use PDO;

final class PdoCustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    /** @return list<Customer> */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, contact_person, phone, email, address FROM customers ORDER BY id ASC');
        $stmt->execute();
        /** @var list<array{id: int|string, code: string, name: string, contact_person: string|null, phone: string|null, email: string|null, address: string|null}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $customers = [];
        foreach ($rows as $row) {
            $customers[] = new Customer(
                (int) $row['id'],
                $row['code'],
                $row['name'],
                $row['contact_person'],
                $row['phone'],
                $row['email'],
                $row['address']
            );
        }

        return $customers;
    }

    public function findById(int $id): ?Customer
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, contact_person, phone, email, address FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        /** @var array{id: int|string, code: string, name: string, contact_person: string|null, phone: string|null, email: string|null, address: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Customer(
            (int) $row['id'],
            $row['code'],
            $row['name'],
            $row['contact_person'],
            $row['phone'],
            $row['email'],
            $row['address']
        );
    }

    public function findByCode(string $code, ?int $excludeId = null): ?Customer
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare('SELECT id, code, name, contact_person, phone, email, address FROM customers WHERE code = ? AND id != ? LIMIT 1');
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id, code, name, contact_person, phone, email, address FROM customers WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
        }

        /** @var array{id: int|string, code: string, name: string, contact_person: string|null, phone: string|null, email: string|null, address: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Customer(
            (int) $row['id'],
            $row['code'],
            $row['name'],
            $row['contact_person'],
            $row['phone'],
            $row['email'],
            $row['address']
        );
    }

    public function create(Customer $customer): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO customers (code, name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $customer->code,
            $customer->name,
            $customer->contactPerson ?: null,
            $customer->phone ?: null,
            $customer->email ?: null,
            $customer->address ?: null
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(Customer $customer): void
    {
        $stmt = $this->pdo->prepare('UPDATE customers SET code = ?, name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE id = ?');
        $stmt->execute([
            $customer->code,
            $customer->name,
            $customer->contactPerson ?: null,
            $customer->phone ?: null,
            $customer->email ?: null,
            $customer->address ?: null,
            $customer->id
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM customers WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getSalesOrderCount(int $customerId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM sales_orders WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        return (int) $stmt->fetchColumn();
    }
}
