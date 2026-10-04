<?php
declare(strict_types=1);

namespace Tests;

use App\Exception\ConflictException;
use App\Exception\ValidationException;
use App\Model\Category;
use App\Repository\CategoryRepositoryInterface;
use App\Service\CategoryService;
use PHPUnit\Framework\TestCase;

final class CategoryServiceTest extends TestCase
{
    public function testCreateCategorySuccess(): void
    {
        $repo = $this->createMock(CategoryRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findByCode')
            ->with('CAT-NEW')
            ->willReturn(null);

        $repo->expects(self::once())
            ->method('create')
            ->with(self::callback(static fn(Category $c) => $c->code === 'CAT-NEW' && $c->name === 'New Category'))
            ->willReturn(10);

        $service = new CategoryService($repo);
        $result = $service->createCategory([
            'code' => 'cat-new',
            'name' => 'New Category',
            'description' => 'Test description',
        ]);

        self::assertSame(10, $result['id']);
        self::assertSame('CAT-NEW', $result['code']);
        self::assertSame('New Category', $result['name']);
    }

    public function testDeleteCategoryWithExistingProductsThrowsConflict(): void
    {
        $repo = $this->createMock(CategoryRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('getProductCount')
            ->with(5)
            ->willReturn(3);

        $service = new CategoryService($repo);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('Kategori tidak dapat dihapus karena masih digunakan oleh 3 produk terdaftar.');

        $service->deleteCategory(5);
    }
}
