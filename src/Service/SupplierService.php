<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Model\Supplier;
use App\Repository\SupplierRepositoryInterface;

final class SupplierService
{
    public function __construct(private SupplierRepositoryInterface $supplierRepository) {}

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createSupplier(array $input): array
    {
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $contact = trim((string) ($input['contact_person'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $email = trim(strtolower((string) ($input['email'] ?? '')));
        $address = trim((string) ($input['address'] ?? ''));

        if ($code === '' || strlen($code) > 20) {
            throw new ValidationException('Kode supplier wajib diisi (maks 20 karakter).');
        }
        if ($name === '' || strlen($name) > 150) {
            throw new ValidationException('Nama perusahaan supplier wajib diisi (maks 150 karakter).');
        }

        if ($this->supplierRepository->findByCode($code) !== null) {
            throw new ConflictException('Kode supplier "' . $code . '" sudah terdaftar.');
        }

        $supplier = new Supplier(0, $code, $name, $contact ?: null, $phone ?: null, $email ?: null, $address ?: null);
        $newId = $this->supplierRepository->create($supplier);

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
    public function updateSupplier(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $contact = trim((string) ($input['contact_person'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $email = trim(strtolower((string) ($input['email'] ?? '')));
        $address = trim((string) ($input['address'] ?? ''));

        if ($id <= 0) {
            throw new ValidationException('ID supplier tidak valid.');
        }
        if ($code === '' || strlen($code) > 20) {
            throw new ValidationException('Kode supplier wajib diisi (maks 20 karakter).');
        }
        if ($name === '' || strlen($name) > 150) {
            throw new ValidationException('Nama supplier wajib diisi (maks 150 karakter).');
        }

        if ($this->supplierRepository->findByCode($code, $id) !== null) {
            throw new ConflictException('Kode supplier "' . $code . '" sudah digunakan oleh supplier lain.');
        }

        $supplier = new Supplier($id, $code, $name, $contact ?: null, $phone ?: null, $email ?: null, $address ?: null);
        $this->supplierRepository->update($supplier);

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
    public function deleteSupplier(int $id): array
    {
        if ($id <= 0) {
            throw new ValidationException('ID supplier tidak valid.');
        }

        $poCount = $this->supplierRepository->getPurchaseOrderCount($id);
        if ($poCount > 0) {
            throw new ConflictException('Supplier tidak dapat dihapus karena memiliki riwayat ' . $poCount . ' Purchase Order (PO).');
        }

        $this->supplierRepository->delete($id);
        return ['message' => 'Data supplier berhasil dihapus dari database.'];
    }
}
