<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\PurchaseOrder;
use App\Model\SalesOrder;
use App\Model\StockLedger;

interface OrderRepositoryInterface
{
    /** @return list<SalesOrder> */
    public function findAllSalesOrders(): array;

    public function findSalesOrderById(int $id): ?SalesOrder;

    /**
     * @param list<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function createSalesOrder(int $customerId, int $warehouseId, int $createdBy, ?string $notes, array $items): int;

    public function updateSalesOrderStatus(int $orderId, string $status, ?int $approvedBy = null): bool;

    public function processGoodsIssue(int $salesOrderId, int $warehouseUserId): void;

    /** @return list<PurchaseOrder> */
    public function findAllPurchaseOrders(): array;

    public function findPurchaseOrderById(int $id): ?PurchaseOrder;

    /**
     * @param list<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function createPurchaseOrder(int $supplierId, int $warehouseId, int $createdBy, ?string $notes, array $items): int;

    public function updatePurchaseOrderStatus(int $orderId, string $status, ?int $approvedBy = null): bool;

    /**
     * @param list<array{product_id: int, quantity: int}>|null $itemsReceived
     */
    public function processGoodsReceipt(int $purchaseOrderId, int $warehouseUserId, ?array $itemsReceived = null): void;

    /** @return list<StockLedger> */
    public function findAllStockLedger(): array;

    /**
     * @return array{sku: string, name: string, unit: string, total_stock: int, warehouses: list<array{warehouse_code: string, warehouse_name: string, quantity: int}>}|null
     */
    public function getProductAvailabilityBySku(string $sku): ?array;
}
