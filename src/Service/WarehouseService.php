<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Model\Warehouse;
use App\Repository\WarehouseRepositoryInterface;
use PDO;
use Throwable;

final class WarehouseService
{
    public function __construct(
        private WarehouseRepositoryInterface $warehouseRepository,
        private PDO $pdo
    ) {}

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createWarehouse(array $input): array
    {
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $city = trim((string) ($input['city'] ?? ''));
        $address = trim((string) ($input['address'] ?? ''));

        if ($code === '' || strlen($code) > 20) {
            throw new ValidationException('Kode gudang wajib diisi (maks 20 karakter).');
        }
        if ($name === '' || strlen($name) > 100) {
            throw new ValidationException('Nama gudang wajib diisi (maks 100 karakter).');
        }
        if ($city === '' || strlen($city) > 50) {
            throw new ValidationException('Kota/wilayah gudang wajib diisi (maks 50 karakter).');
        }

        if ($this->warehouseRepository->findByCode($code) !== null) {
            throw new ConflictException('Kode gudang "' . $code . '" sudah terdaftar.');
        }

        $warehouse = new Warehouse(0, $code, $name, $city, $address ?: null, 1);
        $newId = $this->warehouseRepository->create($warehouse);

        // Otomatis inisialisasi saldo stok 0 untuk produk yang sudah ada di gudang baru ini
        $this->warehouseRepository->initWarehouseStockForProducts($newId);

        return [
            'id' => $newId,
            'code' => $code,
            'name' => $name,
            'city' => $city,
            'address' => $address ?: '-',
            'is_active' => 1,
            'status_label' => 'Aktif Beroperasi',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateWarehouse(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $city = trim((string) ($input['city'] ?? ''));
        $address = trim((string) ($input['address'] ?? ''));
        $isActive = isset($input['is_active']) ? (int) $input['is_active'] : 1;

        if ($id <= 0) {
            throw new ValidationException('ID gudang tidak valid.');
        }
        if ($code === '' || strlen($code) > 20) {
            throw new ValidationException('Kode gudang wajib diisi (maks 20 karakter).');
        }
        if ($name === '' || strlen($name) > 100) {
            throw new ValidationException('Nama gudang wajib diisi (maks 100 karakter).');
        }
        if ($city === '' || strlen($city) > 50) {
            throw new ValidationException('Kota gudang wajib diisi (maks 50 karakter).');
        }

        if ($this->warehouseRepository->findByCode($code, $id) !== null) {
            throw new ConflictException('Kode gudang "' . $code . '" sudah digunakan oleh gudang lain.');
        }

        $warehouse = new Warehouse($id, $code, $name, $city, $address ?: null, $isActive);
        $this->warehouseRepository->update($warehouse);

        return [
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'city' => $city,
            'address' => $address ?: '-',
            'is_active' => $isActive,
            'status_label' => $isActive === 1 ? 'Aktif Beroperasi' : 'Nonaktif',
        ];
    }

    /**
     * @return array{message: string}
     */
    public function deleteWarehouse(int $id): array
    {
        if ($id <= 0) {
            throw new ValidationException('ID gudang tidak valid.');
        }

        $curStock = $this->warehouseRepository->getPhysicalStock($id);
        $orderCount = $this->warehouseRepository->getOrderCount($id);

        if ($curStock > 0 || $orderCount > 0) {
            throw new ConflictException(
                'Gudang tidak dapat dihapus karena masih menyimpan stok fisik (' . $curStock . ' unit) atau memiliki ' .
                $orderCount . ' riwayat transaksi order. Anda dapat menonaktifkannya melalui tombol Edit.'
            );
        }

        $this->pdo->beginTransaction();
        try {
            $this->warehouseRepository->deleteStocksAndLedger($id);
            $this->warehouseRepository->delete($id);
            $this->pdo->commit();

            return ['message' => 'Lokasi gudang berhasil dihapus.'];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
