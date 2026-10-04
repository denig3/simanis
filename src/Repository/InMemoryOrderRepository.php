<?php
declare(strict_types=1);

namespace App\Repository;

use App\Exception\ConflictException;
use App\Exception\InsufficientStockException;
use App\Exception\NotFoundException;
use App\Model\PurchaseOrder;
use App\Model\SalesOrder;
use App\Model\StockLedger;

/**
 * IMPLEMENTASI IN-MEMORY / FAKE REPOSITORY (ARCH-01)
 *
 * Digunakan untuk Unit Testing terisolasi tanpa memerlukan koneksi database nyata (Zero DB).
 * Mensimulasikan penyimpanan di memori untuk Sales Orders, Purchase Orders, dan Stock Ledger.
 */
final class InMemoryOrderRepository implements OrderRepositoryInterface
{
    /** @var array<int, SalesOrder> */
    public array $salesOrders = [];

    /** @var array<int, list<array{product_id: int, quantity: int, unit_price: float}>> */
    public array $salesOrderItems = [];

    /** @var array<int, PurchaseOrder> */
    public array $purchaseOrders = [];

    /** @var array<int, list<array{product_id: int, quantity: int, unit_price: float, quantity_received: int}>> */
    public array $purchaseOrderItems = [];

    /** @var array<string, int> Format key: "warehouseId:productId" => quantity */
    public array $stocks = [];

    /** @var list<StockLedger> */
    public array $stockLedger = [];

    private int $nextSoId = 100;
    private int $nextPoId = 500;
    private int $nextLedgerId = 1;

    /** @return list<SalesOrder> */
    public function findAllSalesOrders(): array
    {
        return array_values($this->salesOrders);
    }

    public function findSalesOrderById(int $id): ?SalesOrder
    {
        return $this->salesOrders[$id] ?? null;
    }

    /** @return list<array{id: int, sales_order_id: int, product_id: int, sku: string, product_name: string, quantity: int, unit_price: float, subtotal: float}> */
    public function findSalesOrderItems(int $salesOrderId): array
    {
        $items = $this->salesOrderItems[$salesOrderId] ?? [];
        $result = [];
        $i = 1;
        foreach ($items as $item) {
            $pId = $item['product_id'];
            $qty = $item['quantity'];
            $price = $item['unit_price'];
            $result[] = [
                'id' => $i++,
                'sales_order_id' => $salesOrderId,
                'product_id' => $pId,
                'sku' => "PRD-{$pId}",
                'product_name' => "Mock Product {$pId}",
                'quantity' => $qty,
                'unit_price' => $price,
                'subtotal' => $qty * $price,
            ];
        }
        return $result;
    }

    /**
     * @param list<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function createSalesOrder(int $customerId, int $warehouseId, int $createdBy, ?string $notes, array $items): int
    {
        $id = $this->nextSoId++;
        $soNumber = 'SO-MOCK-' . $id;

        $totalAmount = 0.0;
        foreach ($items as $item) {
            $totalAmount += $item['quantity'] * $item['unit_price'];
        }

        $this->salesOrders[$id] = new SalesOrder(
            $id,
            $soNumber,
            $customerId,
            $warehouseId,
            'draft',
            $totalAmount,
            $createdBy,
            null,
            $notes,
            date('Y-m-d H:i:s'),
            'Mock Customer',
            'Mock Warehouse',
            'Mock Sales',
            null,
            count($items) . ' items'
        );

        $this->salesOrderItems[$id] = $items;
        return $id;
    }

    public function updateSalesOrderStatus(int $orderId, string $status, ?int $approvedBy = null): bool
    {
        if (!isset($this->salesOrders[$orderId])) {
            return false;
        }

        $old = $this->salesOrders[$orderId];
        $this->salesOrders[$orderId] = new SalesOrder(
            $old->id,
            $old->soNumber,
            $old->customerId,
            $old->warehouseId,
            $status,
            $old->totalAmount,
            $old->createdBy,
            $approvedBy ?? $old->approvedBy,
            $old->notes,
            $old->createdAt,
            $old->customerName,
            $old->warehouseName,
            $old->creatorName,
            $approvedBy !== null ? 'Mock Approver' : $old->approverName,
            $old->itemsSummary
        );

        return true;
    }

    public function processGoodsIssue(int $salesOrderId, int $warehouseUserId): void
    {
        $so = $this->salesOrders[$salesOrderId] ?? null;
        if ($so === null) {
            throw new NotFoundException("Sales Order #{$salesOrderId} tidak ditemukan.");
        }

        if ($so->status !== 'approved') {
            throw new ConflictException("Goods Issue hanya dapat diproses untuk order 'approved'. Status: {$so->status}.");
        }

        $items = $this->salesOrderItems[$salesOrderId] ?? [];
        if (empty($items)) {
            throw new ConflictException("Tidak ada item dalam Sales Order.");
        }

        // Verifikasi dan kurangi stok
        foreach ($items as $item) {
            $key = "{$so->warehouseId}:{$item['product_id']}";
            $currentStock = $this->stocks[$key] ?? 0;

            if ($currentStock < $item['quantity']) {
                throw new InsufficientStockException("Stok tidak mencukupi untuk Produk ID {$item['product_id']}.");
            }

            $this->stocks[$key] = $currentStock - $item['quantity'];

            $this->stockLedger[] = new StockLedger(
                $this->nextLedgerId++,
                $so->warehouseId,
                $item['product_id'],
                'GOODS_ISSUE',
                -$item['quantity'],
                $this->stocks[$key],
                'SO',
                $so->soNumber,
                $warehouseUserId,
                'Fulfillment Goods Issue'
            );
        }

        $this->updateSalesOrderStatus($salesOrderId, 'fulfilled');
    }

    /** @return list<PurchaseOrder> */
    public function findAllPurchaseOrders(): array
    {
        return array_values($this->purchaseOrders);
    }

    public function findPurchaseOrderById(int $id): ?PurchaseOrder
    {
        return $this->purchaseOrders[$id] ?? null;
    }

    /** @return list<array{id: int, purchase_order_id: int, product_id: int, sku: string, product_name: string, quantity: int, quantity_received: int, unit_price: float, subtotal: float}> */
    public function findPurchaseOrderItems(int $purchaseOrderId): array
    {
        $items = $this->purchaseOrderItems[$purchaseOrderId] ?? [];
        $result = [];
        $i = 1;
        foreach ($items as $item) {
            $pId = $item['product_id'];
            $qty = $item['quantity'];
            $qtyRec = $item['quantity_received'];
            $price = $item['unit_price'];
            $result[] = [
                'id' => $i++,
                'purchase_order_id' => $purchaseOrderId,
                'product_id' => $pId,
                'sku' => "PRD-{$pId}",
                'product_name' => "Mock Product {$pId}",
                'quantity' => $qty,
                'quantity_received' => $qtyRec,
                'unit_price' => $price,
                'subtotal' => $qty * $price,
            ];
        }
        return $result;
    }

    /**
     * @param list<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function createPurchaseOrder(int $supplierId, int $warehouseId, int $createdBy, ?string $notes, array $items): int
    {
        $id = $this->nextPoId++;
        $poNumber = 'PO-MOCK-' . $id;

        $totalAmount = 0.0;
        $itemsWithRec = [];
        foreach ($items as $item) {
            $totalAmount += $item['quantity'] * $item['unit_price'];
            $itemsWithRec[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'quantity_received' => 0,
            ];
        }

        $this->purchaseOrders[$id] = new PurchaseOrder(
            $id,
            $poNumber,
            $supplierId,
            $warehouseId,
            'draft',
            $totalAmount,
            $createdBy,
            null,
            $notes,
            date('Y-m-d H:i:s'),
            'Mock Supplier',
            'Mock Warehouse',
            'Mock Creator',
            null,
            count($items) . ' items'
        );

        $this->purchaseOrderItems[$id] = $itemsWithRec;
        return $id;
    }

    public function updatePurchaseOrderStatus(int $orderId, string $status, ?int $approvedBy = null): bool
    {
        if (!isset($this->purchaseOrders[$orderId])) {
            return false;
        }

        $old = $this->purchaseOrders[$orderId];
        $this->purchaseOrders[$orderId] = new PurchaseOrder(
            $old->id,
            $old->poNumber,
            $old->supplierId,
            $old->warehouseId,
            $status,
            $old->totalAmount,
            $old->createdBy,
            $approvedBy ?? $old->approvedBy,
            $old->notes,
            $old->createdAt,
            $old->supplierName,
            $old->warehouseName,
            $old->creatorName,
            $approvedBy !== null ? 'Mock Approver' : $old->approverName,
            $old->itemsSummary
        );

        return true;
    }

    /**
     * @param list<array{product_id: int, quantity: int}>|null $itemsReceived
     */
    public function processGoodsReceipt(int $purchaseOrderId, int $warehouseUserId, ?array $itemsReceived = null): void
    {
        $po = $this->purchaseOrders[$purchaseOrderId] ?? null;
        if ($po === null) {
            throw new NotFoundException("Purchase Order #{$purchaseOrderId} tidak ditemukan.");
        }

        $allowed = ['ordered', 'sent_to_supplier', 'partially_received'];
        if (!in_array($po->status, $allowed, true)) {
            throw new ConflictException("Goods Receipt tidak dapat diproses untuk status: {$po->status}.");
        }

        $items = &$this->purchaseOrderItems[$purchaseOrderId];
        $allFulfilled = true;

        foreach ($items as &$item) {
            $qtyToRec = $item['quantity'] - $item['quantity_received'];
            if ($qtyToRec > 0) {
                $key = "{$po->warehouseId}:{$item['product_id']}";
                $current = $this->stocks[$key] ?? 0;
                $this->stocks[$key] = $current + $qtyToRec;
                $item['quantity_received'] += $qtyToRec;

                $this->stockLedger[] = new StockLedger(
                    $this->nextLedgerId++,
                    $po->warehouseId,
                    $item['product_id'],
                    'GOODS_RECEIPT',
                    $qtyToRec,
                    $this->stocks[$key],
                    'PO',
                    $po->poNumber,
                    $warehouseUserId,
                    'Goods receipt mock'
                );
            }
            if ($item['quantity_received'] < $item['quantity']) {
                $allFulfilled = false;
            }
        }
        unset($item);

        $newStatus = $allFulfilled ? 'goods_received' : 'partially_received';
        $this->updatePurchaseOrderStatus($purchaseOrderId, $newStatus);
    }

    /** @return list<StockLedger> */
    public function findAllStockLedger(): array
    {
        return $this->stockLedger;
    }

    public function getProductAvailabilityBySku(string $sku): ?array
    {
        if ($sku === 'NONEXISTENT') {
            return null;
        }

        return [
            'sku' => $sku,
            'name' => 'Mock Product ' . $sku,
            'unit' => 'unit',
            'total_stock' => 50,
            'warehouses' => [
                ['warehouse_code' => 'WH-JKT', 'warehouse_name' => 'Gudang Jakarta', 'quantity' => 30],
                ['warehouse_code' => 'WH-SBY', 'warehouse_name' => 'Gudang Surabaya', 'quantity' => 20],
            ],
        ];
    }
}
