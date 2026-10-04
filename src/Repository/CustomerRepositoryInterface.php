<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Customer;

interface CustomerRepositoryInterface
{
    /** @return list<Customer> */
    public function findAll(): array;
    public function findById(int $id): ?Customer;
    public function findByCode(string $code, ?int $excludeId = null): ?Customer;
    public function create(Customer $customer): int;
    public function update(Customer $customer): void;
    public function delete(int $id): void;
    public function getSalesOrderCount(int $customerId): int;
}
