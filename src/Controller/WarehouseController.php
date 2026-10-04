<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\WarehouseService;
use Throwable;

final class WarehouseController extends BaseController
{
    public function __construct(private WarehouseService $warehouseService) {}

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name: string, email: string, role?: string, status?: string}|null $sessionUser
     */
    public function create(array $input, ?array $sessionUser): never
    {
        if ($sessionUser === null) {
            self::json(['message' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
        }

        if (($sessionUser['role'] ?? '') !== 'admin') {
            self::json(['message' => 'Akses terlarang (403). Hanya Administrator yang berwenang menambah lokasi gudang.'], 403);
        }

        try {
            $warehouse = $this->warehouseService->createWarehouse($input);
            self::json([
                'message' => 'Lokasi gudang baru "' . ($warehouse['name'] ?? '') . '" berhasil ditambahkan.',
                'warehouse' => $warehouse,
            ], 201);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name: string, email: string, role?: string, status?: string}|null $sessionUser
     */
    public function update(array $input, ?array $sessionUser): never
    {
        if ($sessionUser === null) {
            self::json(['message' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
        }

        if (($sessionUser['role'] ?? '') !== 'admin') {
            self::json(['message' => 'Akses terlarang (403). Hanya Administrator yang berwenang mengubah data gudang.'], 403);
        }

        try {
            $warehouse = $this->warehouseService->updateWarehouse($input);
            self::json([
                'message' => 'Data gudang "' . ($warehouse['name'] ?? '') . '" berhasil diperbarui.',
                'warehouse' => $warehouse,
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name: string, email: string, role?: string, status?: string}|null $sessionUser
     */
    public function delete(array $input, ?array $sessionUser): never
    {
        if ($sessionUser === null) {
            self::json(['message' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
        }

        if (($sessionUser['role'] ?? '') !== 'admin') {
            self::json(['message' => 'Akses terlarang (403). Hanya Administrator yang berwenang menghapus gudang.'], 403);
        }

        try {
            $id = (int) ($input['id'] ?? 0);
            $result = $this->warehouseService->deleteWarehouse($id);
            self::json($result);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }
}
