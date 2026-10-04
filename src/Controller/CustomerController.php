<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CustomerService;
use Throwable;

final class CustomerController extends BaseController
{
    public function __construct(private CustomerService $customerService) {}

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
            self::json(['message' => 'Akses terlarang (403). Hanya Administrator yang berwenang menambah customer.'], 403);
        }

        try {
            $customer = $this->customerService->createCustomer($input);
            self::json([
                'message' => 'Customer baru "' . ($customer['name'] ?? '') . '" berhasil ditambahkan.',
                'customer' => $customer,
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
            self::json(['message' => 'Akses terlarang (403). Hanya Administrator yang berwenang mengubah data customer.'], 403);
        }

        try {
            $customer = $this->customerService->updateCustomer($input);
            self::json([
                'message' => 'Data customer "' . ($customer['name'] ?? '') . '" berhasil diperbarui.',
                'customer' => $customer,
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
            self::json(['message' => 'Akses terlarang (403). Hanya Administrator yang berwenang menghapus data customer.'], 403);
        }

        try {
            $id = (int) ($input['id'] ?? 0);
            $result = $this->customerService->deleteCustomer($id);
            self::json($result);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }
}
