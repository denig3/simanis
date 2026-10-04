<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Model\Category;
use App\Repository\CategoryRepositoryInterface;

final class CategoryService
{
    public function __construct(private CategoryRepositoryInterface $categoryRepository) {}

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createCategory(array $input): array
    {
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $desc = trim((string) ($input['description'] ?? ''));

        if ($code === '' || strlen($code) > 20) {
            throw new ValidationException('Kode kategori wajib diisi (maks 20 karakter).');
        }
        if ($name === '' || strlen($name) > 100) {
            throw new ValidationException('Nama kategori wajib diisi (maks 100 karakter).');
        }

        if ($this->categoryRepository->findByCode($code) !== null) {
            throw new ConflictException('Kode kategori "' . $code . '" sudah terdaftar.');
        }

        $category = new Category(0, $code, $name, $desc ?: null);
        $newId = $this->categoryRepository->create($category);

        return [
            'id' => $newId,
            'code' => $code,
            'name' => $name,
            'description' => $desc ?: '-',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateCategory(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $desc = trim((string) ($input['description'] ?? ''));

        if ($id <= 0) {
            throw new ValidationException('ID kategori tidak valid.');
        }
        if ($code === '' || strlen($code) > 20) {
            throw new ValidationException('Kode kategori wajib diisi (maks 20 karakter).');
        }
        if ($name === '' || strlen($name) > 100) {
            throw new ValidationException('Nama kategori wajib diisi (maks 100 karakter).');
        }

        if ($this->categoryRepository->findByCode($code, $id) !== null) {
            throw new ConflictException('Kode kategori "' . $code . '" sudah digunakan oleh kategori lain.');
        }

        $category = new Category($id, $code, $name, $desc ?: null);
        $this->categoryRepository->update($category);

        return [
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => $desc ?: '-',
        ];
    }

    /**
     * @return array{message: string}
     */
    public function deleteCategory(int $id): array
    {
        if ($id <= 0) {
            throw new ValidationException('ID kategori tidak valid.');
        }

        $prodCount = $this->categoryRepository->getProductCount($id);
        if ($prodCount > 0) {
            throw new ConflictException('Kategori tidak dapat dihapus karena masih digunakan oleh ' . $prodCount . ' produk terdaftar.');
        }

        $this->categoryRepository->delete($id);
        return ['message' => 'Kategori berhasil dihapus dari database.'];
    }
}
