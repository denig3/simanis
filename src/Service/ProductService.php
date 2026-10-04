<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Model\Product;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use PDO;
use Throwable;

final class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private PDO $pdo
    ) {}

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createProduct(array $input, int $userId): array
    {
        $sku = strtoupper(trim((string) ($input['sku'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $categoryId = (int) ($input['category_id'] ?? 0);
        $unit = trim((string) ($input['unit'] ?? 'unit')) ?: 'unit';
        $purchasePrice = max(0.0, (float) ($input['purchase_price'] ?? 0));
        $sellingPrice = max(0.0, (float) ($input['selling_price'] ?? 0));
        $minStock = max(0, (int) ($input['min_stock_threshold'] ?? 10));
        $stockJkt = max(0, (int) ($input['stock_jkt'] ?? 0));
        $stockSby = max(0, (int) ($input['stock_sby'] ?? 0));
        $stockBdg = max(0, (int) ($input['stock_bdg'] ?? 0));

        if ($sku === '' || strlen($sku) > 50) {
            throw new ValidationException('Kode SKU produk wajib diisi (maksimal 50 karakter).');
        }
        if ($name === '' || strlen($name) > 150) {
            throw new ValidationException('Nama produk wajib diisi (maksimal 150 karakter).');
        }
        if ($categoryId <= 0) {
            throw new ValidationException('Pilih kategori produk yang valid.');
        }
        if ($sellingPrice < 0) {
            throw new ValidationException('Harga jual tidak boleh bernilai negatif.');
        }

        if ($this->productRepository->findBySku($sku) !== null) {
            throw new ConflictException('Kode SKU "' . $sku . '" sudah digunakan oleh produk lain.');
        }

        $category = $this->categoryRepository->findById($categoryId);
        if ($category === null) {
            throw new NotFoundException('Kategori produk yang dipilih tidak ditemukan.');
        }

        $this->pdo->beginTransaction();
        try {
            $product = new Product(
                0,
                $sku,
                $name,
                $categoryId,
                $unit,
                $purchasePrice,
                $sellingPrice,
                $minStock,
                1
            );

            $prodId = $this->productRepository->create($product);

            $warehouseStocks = [
                1 => $stockJkt,
                2 => $stockSby,
                3 => $stockBdg,
            ];
            $totalStock = $stockJkt + $stockSby + $stockBdg;

            $this->productRepository->initProductStocks($prodId, $warehouseStocks, $userId, $sku);

            $this->pdo->commit();

            return [
                'id' => $prodId,
                'sku' => $sku,
                'name' => $name,
                'category_id' => $categoryId,
                'category_name' => $category->name,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'unit' => $unit,
                'min_stock_threshold' => $minStock,
                'stock_jkt' => $stockJkt,
                'stock_sby' => $stockSby,
                'stock_bdg' => $stockBdg,
                'total_stock' => $totalStock,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateProduct(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        $sku = strtoupper(trim((string) ($input['sku'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $categoryId = (int) ($input['category_id'] ?? 0);
        $unit = trim((string) ($input['unit'] ?? 'unit')) ?: 'unit';
        $purchasePrice = max(0.0, (float) ($input['purchase_price'] ?? 0));
        $sellingPrice = max(0.0, (float) ($input['selling_price'] ?? 0));
        $minStock = max(0, (int) ($input['min_stock_threshold'] ?? 10));
        $isActive = isset($input['is_active']) ? (int) $input['is_active'] : 1;

        if ($id <= 0) {
            throw new ValidationException('ID produk tidak valid.');
        }
        if ($sku === '' || strlen($sku) > 50) {
            throw new ValidationException('Kode SKU produk wajib diisi (maks 50 karakter).');
        }
        if ($name === '' || strlen($name) > 150) {
            throw new ValidationException('Nama produk wajib diisi (maks 150 karakter).');
        }
        if ($categoryId <= 0) {
            throw new ValidationException('Pilih kategori produk yang valid.');
        }

        $existingProduct = $this->productRepository->findById($id);
        if ($existingProduct === null) {
            throw new NotFoundException('Produk tidak ditemukan.');
        }

        if ($sku !== $existingProduct->sku && $this->productRepository->findBySku($sku, $id) !== null) {
            throw new ConflictException('Kode SKU "' . $sku . '" sudah digunakan oleh produk lain.');
        }

        $category = $this->categoryRepository->findById($categoryId);
        if ($category === null) {
            throw new NotFoundException('Kategori produk yang dipilih tidak ditemukan.');
        }

        $productToUpdate = new Product(
            $id,
            $sku,
            $name,
            $categoryId,
            $unit,
            $purchasePrice,
            $sellingPrice,
            $minStock,
            $isActive
        );

        $this->productRepository->update($productToUpdate);
        $totalStock = $this->productRepository->getTotalStock($id);

        return [
            'id' => $id,
            'sku' => $sku,
            'name' => $name,
            'category_id' => $categoryId,
            'category_name' => $category->name,
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'unit' => $unit,
            'min_stock_threshold' => $minStock,
            'is_active' => $isActive,
            'total_stock' => $totalStock,
        ];
    }

    /**
     * @return array{message: string}
     */
    public function deleteProduct(int $id): array
    {
        if ($id <= 0) {
            throw new ValidationException('ID produk tidak valid.');
        }

        $product = $this->productRepository->findById($id);
        if ($product === null) {
            throw new NotFoundException('Produk tidak ditemukan.');
        }

        $counts = $this->productRepository->getTransactionCount($id);
        if ($counts['so'] > 0 || $counts['po'] > 0) {
            throw new ConflictException(
                'Produk "' . $product->name . '" tidak dapat dihapus permanen karena memiliki riwayat transaksi (' .
                $counts['so'] . ' SO, ' . $counts['po'] . ' PO). Anda dapat mengubah statusnya menjadi Nonaktif melalui tombol Edit.'
            );
        }

        $this->pdo->beginTransaction();
        try {
            $this->productRepository->deleteStocksAndLedger($id);
            $this->productRepository->delete($id);
            $this->pdo->commit();

            return ['message' => 'Produk "' . $product->name . '" berhasil dihapus dari database.'];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
