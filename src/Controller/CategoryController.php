<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CategoryService;
use Throwable;

final class CategoryController extends BaseController
{
    public function __construct(private CategoryService $categoryService) {}

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
            $category = $this->categoryService->createCategory($input);
            self::json([
                'message' => 'Kategori baru "' . ($category['name'] ?? '') . '" berhasil ditambahkan.',
                'category' => $category,
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
            $category = $this->categoryService->updateCategory($input);
            self::json([
                'message' => 'Kategori "' . ($category['name'] ?? '') . '" berhasil diperbarui.',
                'category' => $category,
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
            $result = $this->categoryService->deleteCategory($id);
            self::json($result);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }
}
