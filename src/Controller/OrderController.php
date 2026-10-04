<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\UnauthorizedException;
use App\Service\OrderService;
use Throwable;

final class OrderController extends BaseController
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function getSalesOrderDetail(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            $data = $this->orderService->getSalesOrderDetails($id);
            self::json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function createSalesOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = $this->orderService->createSalesOrder($user, $input);
            self::json([
                'success' => true,
                'message' => 'Sales Order berhasil dibuat.',
                'order_id' => $id,
            ], 201);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function submitSalesOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            $this->orderService->submitSalesOrder($user, $id);
            self::json([
                'success' => true,
                'message' => 'Sales Order berhasil diajukan untuk ditinjau.',
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function approveSalesOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            $this->orderService->approveSalesOrder($user, $id);
            self::json([
                'success' => true,
                'message' => 'Sales Order berhasil disetujui.',
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function rejectSalesOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            $reason = isset($input['reason']) && is_string($input['reason']) ? trim($input['reason']) : null;
            $this->orderService->rejectSalesOrder($user, $id, $reason);
            self::json([
                'success' => true,
                'message' => 'Sales Order ditolak.',
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function fulfillSalesOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            $this->orderService->fulfillSalesOrder($user, $id);
            self::json([
                'success' => true,
                'message' => 'Barang berhasil dikeluarkan (Goods Issue) dan pesanan terpenuhi.',
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function cancelSalesOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            $this->orderService->cancelSalesOrder($user, $id);
            self::json([
                'success' => true,
                'message' => 'Sales Order berhasil dibatalkan.',
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function getPurchaseOrderDetail(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            $order = $this->orderService->getPurchaseOrder($id);
            $items = $this->orderService->getPurchaseOrderItems($id);
            self::json([
                'success' => true,
                'data' => [
                    'order' => $order->toArray(),
                    'items' => $items,
                ],
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function createPurchaseOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = $this->orderService->createPurchaseOrder($user, $input);
            self::json([
                'success' => true,
                'message' => 'Purchase Order berhasil dibuat.',
                'order_id' => $id,
            ], 201);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function orderPurchaseOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            $this->orderService->orderPurchaseOrder($user, $id);
            self::json([
                'success' => true,
                'message' => 'Purchase Order berhasil dipesan ke supplier.',
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function receivePurchaseOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            /** @var list<array{product_id: int, quantity: int}>|null $itemsReceived */
            $itemsReceived = isset($input['items']) && is_array($input['items']) ? $input['items'] : null;
            $this->orderService->receivePurchaseOrder($user, $id, $itemsReceived);
            self::json([
                'success' => true,
                'message' => 'Barang berhasil diterima (Goods Receipt) dan stok bertambah.',
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function cancelPurchaseOrder(array $input, ?array $user): void
    {
        if ($user === null) {
            throw new UnauthorizedException();
        }

        try {
            $id = (int) ($input['order_id'] ?? 0);
            $this->orderService->cancelPurchaseOrder($user, $id);
            self::json([
                'success' => true,
                'message' => 'Purchase Order berhasil dibatalkan.',
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * Endpoint API JSON: GET /api/products/{sku}/availability
     *
     * @param array{id: int|string, name?: string, email?: string, role?: string}|null $user
     */
    public function getAvailability(string $sku, ?array $user): void
    {
        if ($user === null) {
            self::json(['message' => 'Autentikasi diperlukan untuk mengakses API inventaris.'], 401);
        }

        try {
            $data = $this->orderService->getProductAvailability($sku);
            self::json($data, 200);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }
}
