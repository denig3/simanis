<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\SupplierService;
use Throwable;

final class SupplierController extends BaseController
{
    public function __construct(private SupplierService $supplierService) {}

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name: string, email: string}|null $sessionUser
     */
    public function create(array $input, ?array $sessionUser): never
    {
        if ($sessionUser === null) {
            self::json(['message' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
        }

        try {
            $supplier = $this->supplierService->createSupplier($input);
            self::json([
                'message' => 'Supplier baru "' . ($supplier['name'] ?? '') . '" berhasil ditambahkan.',
                'supplier' => $supplier,
            ], 201);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name: string, email: string}|null $sessionUser
     */
    public function update(array $input, ?array $sessionUser): never
    {
        if ($sessionUser === null) {
            self::json(['message' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
        }

        try {
            $supplier = $this->supplierService->updateSupplier($input);
            self::json([
                'message' => 'Data supplier "' . ($supplier['name'] ?? '') . '" berhasil diperbarui.',
                'supplier' => $supplier,
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name: string, email: string}|null $sessionUser
     */
    public function delete(array $input, ?array $sessionUser): never
    {
        if ($sessionUser === null) {
            self::json(['message' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
        }

        try {
            $id = (int) ($input['id'] ?? 0);
            $result = $this->supplierService->deleteSupplier($id);
            self::json($result);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }
}
