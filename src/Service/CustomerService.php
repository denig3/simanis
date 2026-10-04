<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Model\Customer;
use App\Repository\CustomerRepositoryInterface;

final class CustomerService
{
    public function __construct(private CustomerRepositoryInterface $customerRepository) {}

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createCustomer(array $input): array
    {
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $contact = trim((string) ($input['contact_person'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $email = trim(strtolower((string) ($input['email'] ?? '')));
        $address = trim((string) ($input['address'] ?? ''));

        if ($code === '' || strlen($code) > 20) {
            throw new ValidationException('Kode customer wajib diisi (maks 20 karakter).');
        }
        if ($name === '' || strlen($name) > 150) {
            throw new ValidationException('Nama customer wajib diisi (maks 150 karakter).');
        }

        if ($this->customerRepository->findByCode($code) !== null) {
            throw new ConflictException('Kode customer "' . $code . '" sudah terdaftar.');
        }

        $customer = new Customer(0, $code, $name, $contact ?: null, $phone ?: null, $email ?: null, $address ?: null);
        $newId = $this->customerRepository->create($customer);

        return [
            'id' => $newId,
            'code' => $code,
            'name' => $name,
            'contact_person' => $contact ?: '-',
            'phone' => $phone ?: '-',
            'email' => $email ?: '-',
            'address' => $address ?: '-',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateCustomer(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $contact = trim((string) ($input['contact_person'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $email = trim(strtolower((string) ($input['email'] ?? '')));
        $address = trim((string) ($input['address'] ?? ''));

        if ($id <= 0) {
            throw new ValidationException('ID customer tidak valid.');
        }
        if ($code === '' || strlen($code) > 20) {
            throw new ValidationException('Kode customer wajib diisi (maks 20 karakter).');
        }
        if ($name === '' || strlen($name) > 150) {
            throw new ValidationException('Nama customer wajib diisi (maks 150 karakter).');
        }

        if ($this->customerRepository->findByCode($code, $id) !== null) {
            throw new ConflictException('Kode customer "' . $code . '" sudah digunakan oleh customer lain.');
        }

        $customer = new Customer($id, $code, $name, $contact ?: null, $phone ?: null, $email ?: null, $address ?: null);
        $this->customerRepository->update($customer);

        return [
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'contact_person' => $contact ?: '-',
            'phone' => $phone ?: '-',
            'email' => $email ?: '-',
            'address' => $address ?: '-',
        ];
    }

    /**
     * @return array{message: string}
     */
    public function deleteCustomer(int $id): array
    {
        if ($id <= 0) {
            throw new ValidationException('ID customer tidak valid.');
        }

        $soCount = $this->customerRepository->getSalesOrderCount($id);
        if ($soCount > 0) {
            throw new ConflictException('Customer tidak dapat dihapus karena memiliki riwayat ' . $soCount . ' Sales Order (SO).');
        }

        $this->customerRepository->delete($id);
        return ['message' => 'Data customer berhasil dihapus dari database.'];
    }
}
