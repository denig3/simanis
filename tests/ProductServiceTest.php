<?php
declare(strict_types=1);

namespace Tests;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Model\Category;
use App\Model\Product;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Service\ProductService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ProductServiceTest extends TestCase
{
    public function testDeleteProductWithTransactionHistoryThrowsConflict(): void
    {
        $prodRepo = $this->createMock(ProductRepositoryInterface::class);
        $catRepo = $this->createStub(CategoryRepositoryInterface::class);
        $pdo = $this->createStub(PDO::class);

        $prod = new Product(1, 'SKU-001', 'Test Product', 1);
        $prodRepo->method('findById')->with(1)->willReturn($prod);
        $prodRepo->method('getTransactionCount')->with(1)->willReturn(['so' => 2, 'po' => 0]);

        $service = new ProductService($prodRepo, $catRepo, $pdo);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('memiliki riwayat transaksi');

        $service->deleteProduct(1);
    }

    public function testCreateProductWithUnknownCategoryThrowsNotFound(): void
    {
        $prodRepo = $this->createStub(ProductRepositoryInterface::class);
        $catRepo = $this->createMock(CategoryRepositoryInterface::class);
        $pdo = $this->createStub(PDO::class);

        $prodRepo->method('findBySku')->willReturn(null);
        $catRepo->expects(self::once())->method('findById')->with(999)->willReturn(null);

        $service = new ProductService($prodRepo, $catRepo, $pdo);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Kategori produk yang dipilih tidak ditemukan.');

        $service->createProduct([
            'sku' => 'PRD-NEW',
            'name' => 'Brand New Product',
            'category_id' => 999,
        ], 1);
    }
}
