<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ProductService;
use Throwable;

final class ProductController extends BaseController
{
    public function __construct(private ProductService $productService) {}

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
            $userId = (int) $sessionUser['id'];
            $product = $this->productService->createProduct($input, $userId);
            self::json([
                'message' => 'Produk baru "' . ($product['name'] ?? '') . '" berhasil disimpan.',
                'product' => $product,
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
            $product = $this->productService->updateProduct($input);
            self::json([
                'message' => 'Master produk "' . ($product['name'] ?? '') . '" berhasil diperbarui.',
                'product' => $product,
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
            $result = $this->productService->deleteProduct($id);
            self::json($result);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }
}
