<?php
declare(strict_types=1);

namespace App\Repository;

use App\Exception\ConflictException;
use App\Exception\InsufficientStockException;
use App\Exception\NotFoundException;
use App\Model\PurchaseOrder;
use App\Model\SalesOrder;
use App\Model\StockLedger;
use PDO;
use Throwable;

final class PdoOrderRepository implements OrderRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    /** @return list<SalesOrder> */
    public function findAllSalesOrders(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                so.id, so.so_number, so.customer_id, so.warehouse_id, so.status, so.total_amount, so.notes, so.created_at, so.created_by, so.approved_by,
                c.name AS customer_name,
                w.name AS warehouse_name,
                u.name AS creator_name,
                app.name AS approver_name,
                COALESCE(GROUP_CONCAT(CONCAT(p.name, ' (', soi.quantity, ' ', p.unit, ')') SEPARATOR ', '), 'Tidak ada item') AS items_summary
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            JOIN warehouses w ON so.warehouse_id = w.id
            JOIN users u ON so.created_by = u.id
            LEFT JOIN users app ON so.approved_by = app.id
            LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id
            LEFT JOIN products p ON soi.product_id = p.id
            GROUP BY so.id, so.so_number, so.customer_id, so.warehouse_id, so.status, so.total_amount, so.notes, so.created_at, so.created_by, so.approved_by, c.name, w.name, u.name, app.name
            ORDER BY so.id DESC
        ");
        $stmt->execute();
        /** @var list<array{id: int|string, so_number: string, customer_id: int|string, warehouse_id: int|string, status: string, total_amount: float|string, notes: string|null, created_at: string, created_by: int|string, approved_by: int|string|null, customer_name: string|null, warehouse_name: string|null, creator_name: string|null, approver_name: string|null, items_summary: string|null}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $orders = [];
        foreach ($rows as $row) {
            $orders[] = new SalesOrder(
                (int) $row['id'],
                $row['so_number'],
                (int) $row['customer_id'],
                (int) $row['warehouse_id'],
                $row['status'],
                (float) $row['total_amount'],
                (int) $row['created_by'],
                $row['approved_by'] !== null ? (int) $row['approved_by'] : null,
                $row['notes'],
                $row['created_at'],
                $row['customer_name'],
                $row['warehouse_name'],
                $row['creator_name'],
                $row['approver_name'],
                $row['items_summary'] ?? 'Tidak ada item'
            );
        }

        return $orders;
    }

    public function findSalesOrderById(int $id): ?SalesOrder
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                so.id, so.so_number, so.customer_id, so.warehouse_id, so.status, so.total_amount, so.notes, so.created_at, so.created_by, so.approved_by,
                c.name AS customer_name,
                w.name AS warehouse_name,
                u.name AS creator_name,
                app.name AS approver_name,
                COALESCE(GROUP_CONCAT(CONCAT(p.name, ' (', soi.quantity, ' ', p.unit, ')') SEPARATOR ', '), 'Tidak ada item') AS items_summary
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            JOIN warehouses w ON so.warehouse_id = w.id
            JOIN users u ON so.created_by = u.id
            LEFT JOIN users app ON so.approved_by = app.id
            LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id
            LEFT JOIN products p ON soi.product_id = p.id
            WHERE so.id = ?
            GROUP BY so.id, so.so_number, so.customer_id, so.warehouse_id, so.status, so.total_amount, so.notes, so.created_at, so.created_by, so.approved_by, c.name, w.name, u.name, app.name
            LIMIT 1
        ");
        $stmt->execute([$id]);
        /** @var array{id: int|string, so_number: string, customer_id: int|string, warehouse_id: int|string, status: string, total_amount: float|string, notes: string|null, created_at: string, created_by: int|string, approved_by: int|string|null, customer_name: string|null, warehouse_name: string|null, creator_name: string|null, approver_name: string|null, items_summary: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new SalesOrder(
            (int) $row['id'],
            $row['so_number'],
            (int) $row['customer_id'],
            (int) $row['warehouse_id'],
            $row['status'],
            (float) $row['total_amount'],
            (int) $row['created_by'],
            $row['approved_by'] !== null ? (int) $row['approved_by'] : null,
            $row['notes'],
            $row['created_at'],
            $row['customer_name'],
            $row['warehouse_name'],
            $row['creator_name'],
            $row['approver_name'],
            $row['items_summary'] ?? 'Tidak ada item'
        );
    }

    /** @return list<array{id: int, sales_order_id: int, product_id: int, sku: string, product_name: string, quantity: int, unit_price: float, subtotal: float}> */
    public function findSalesOrderItems(int $salesOrderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT soi.id, soi.sales_order_id, soi.product_id, p.sku, p.name AS product_name, soi.quantity, soi.unit_price, soi.subtotal
            FROM sales_order_items soi
            JOIN products p ON soi.product_id = p.id
            WHERE soi.sales_order_id = ?
            ORDER BY soi.id ASC
        ");
        $stmt->execute([$salesOrderId]);
        /** @var list<array{id: int|string, sales_order_id: int|string, product_id: int|string, sku: string, product_name: string, quantity: int|string, unit_price: float|string, subtotal: float|string}> $raw */
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($raw as $item) {
            $result[] = [
                'id' => (int) $item['id'],
                'sales_order_id' => (int) $item['sales_order_id'],
                'product_id' => (int) $item['product_id'],
                'sku' => (string) $item['sku'],
                'product_name' => (string) $item['product_name'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'subtotal' => (float) $item['subtotal'],
            ];
        }
        return $result;
    }

    /**
     * @param list<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function createSalesOrder(int $customerId, int $warehouseId, int $createdBy, ?string $notes, array $items): int
    {
        $this->pdo->beginTransaction();
        try {
            $totalAmount = 0.0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $soNumber = 'SO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            $stmt = $this->pdo->prepare("
                INSERT INTO sales_orders (so_number, customer_id, warehouse_id, status, total_amount, created_by, notes, created_at)
                VALUES (?, ?, ?, 'draft', ?, ?, ?, NOW())
            ");
            $stmt->execute([$soNumber, $customerId, $warehouseId, $totalAmount, $createdBy, $notes]);
            $orderId = (int) $this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare("
                INSERT INTO sales_order_items (sales_order_id, product_id, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price'], $subtotal]);
            }

            $this->pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateSalesOrderStatus(int $orderId, string $status, ?int $approvedBy = null): bool
    {
        if ($approvedBy !== null) {
            $stmt = $this->pdo->prepare("
                UPDATE sales_orders 
                SET status = ?, approved_by = ?, approved_at = NOW(), updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$status, $approvedBy, $orderId]);
        }

        $stmt = $this->pdo->prepare("
            UPDATE sales_orders 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $orderId]);
    }

    /**
     * Memproses Goods Issue dengan PESSIMISTIC LOCKING (SELECT ... FOR UPDATE)
     * Mengurangi ProductStock dan menulis baris StockLedger bertipe Issue dalam satu transaksi ACID (ARCH-02).
     */
    public function processGoodsIssue(int $salesOrderId, int $warehouseUserId): void
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Ambil data Sales Order dan pastikan statusnya 'approved'
            $stmtSO = $this->pdo->prepare("
                SELECT id, so_number, warehouse_id, status 
                FROM sales_orders 
                WHERE id = ? 
                LIMIT 1 
                FOR UPDATE
            ");
            $stmtSO->execute([$salesOrderId]);
            /** @var array{id: int|string, so_number: string, warehouse_id: int|string, status: string}|false $so */
            $so = $stmtSO->fetch(PDO::FETCH_ASSOC);

            if ($so === false) {
                throw new NotFoundException("Sales Order #{$salesOrderId} tidak ditemukan.");
            }

            if ($so['status'] !== 'approved') {
                throw new ConflictException("Goods Issue hanya dapat diproses untuk Sales Order berstatus 'approved'. Status saat ini: {$so['status']}.");
            }

            $warehouseId = (int) $so['warehouse_id'];
            $soNumber = $so['so_number'];

            // 2. Ambil seluruh item dalam Sales Order
            $stmtItems = $this->pdo->prepare("
                SELECT product_id, quantity 
                FROM sales_order_items 
                WHERE sales_order_id = ?
            ");
            $stmtItems->execute([$salesOrderId]);
            /** @var list<array{product_id: int|string, quantity: int|string}> $items */
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                throw new ConflictException("Sales Order tidak memiliki item untuk dikeluarkan.");
            }

            // Siapkan statement prepared untuk locking, update stok, dan ledger
            $stmtLock = $this->pdo->prepare("
                SELECT quantity_on_hand 
                FROM inventory_stocks 
                WHERE product_id = ? AND warehouse_id = ? 
                FOR UPDATE
            ");

            $stmtUpdateStock = $this->pdo->prepare("
                UPDATE inventory_stocks 
                SET quantity_on_hand = quantity_on_hand - ? 
                WHERE product_id = ? AND warehouse_id = ?
            ");

            $stmtLedger = $this->pdo->prepare("
                INSERT INTO stock_ledger (
                    warehouse_id, product_id, movement_type, quantity_delta, balance_after,
                    reference_doc_type, reference_doc_number, user_id, notes, created_at
                ) VALUES (?, ?, 'GOODS_ISSUE', ?, ?, 'SO', ?, ?, ?, NOW())
            ");

            // 3. Iterasi setiap item, terapkan PESSIMISTIC LOCKING dan validasi stok
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $requestedQty = (int) $item['quantity'];

                // Kunci baris stok produk di gudang terkait
                $stmtLock->execute([$productId, $warehouseId]);
                $stockRow = $stmtLock->fetch(PDO::FETCH_ASSOC);

                if ($stockRow === false) {
                    throw new InsufficientStockException("Stok belum terdaftar untuk Produk ID {$productId} di Gudang ID {$warehouseId}.");
                }

                $currentQtyOnHand = (int) $stockRow['quantity_on_hand'];

                // Jika stok fisik kurang dari permintaan, gagalkan seluruh transaksi!
                if ($currentQtyOnHand < $requestedQty) {
                    throw new InsufficientStockException(
                        "Stok tidak mencukupi untuk Produk ID {$productId}. Tersedia: {$currentQtyOnHand}, dibutuhkan: {$requestedQty}."
                    );
                }

                $newBalance = $currentQtyOnHand - $requestedQty;

                // Kurangi stok fisik di gudang
                $stmtUpdateStock->execute([$requestedQty, $productId, $warehouseId]);

                // Catat ke Immutable Stock Ledger (Audit Trail)
                $notes = "Goods Issue untuk pemenuhan {$soNumber}";
                $stmtLedger->execute([
                    $warehouseId,
                    $productId,
                    -$requestedQty,
                    $newBalance,
                    $soNumber,
                    $warehouseUserId,
                    $notes
                ]);
            }

            // 4. Catat dokumen Goods Issue
            $giNumber = 'GI-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $stmtGI = $this->pdo->prepare("
                INSERT INTO goods_issues (gi_number, sales_order_id, warehouse_id, issued_by, issue_date, notes)
                VALUES (?, ?, ?, ?, NOW(), 'Fulfillment Sales Order')
            ");
            $stmtGI->execute([$giNumber, $salesOrderId, $warehouseId, $warehouseUserId]);
            $giId = (int) $this->pdo->lastInsertId();

            $stmtGII = $this->pdo->prepare("
                INSERT INTO goods_issue_items (goods_issue_id, product_id, quantity_issued)
                VALUES (?, ?, ?)
            ");
            foreach ($items as $item) {
                $stmtGII->execute([$giId, (int) $item['product_id'], (int) $item['quantity']]);
            }

            // 5. Perbarui status Sales Order menjadi 'fulfilled'
            $stmtFulfill = $this->pdo->prepare("
                UPDATE sales_orders 
                SET status = 'fulfilled', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmtFulfill->execute([$salesOrderId]);

            // 6. Commit transaksi secara atomik
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return list<PurchaseOrder> */
    public function findAllPurchaseOrders(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                po.id, po.po_number, po.supplier_id, po.warehouse_id, po.status, po.total_amount, po.notes, po.created_at, po.created_by, po.approved_by,
                s.name AS supplier_name,
                w.name AS warehouse_name,
                u.name AS creator_name,
                app.name AS approver_name,
                COALESCE(GROUP_CONCAT(CONCAT(p.name, ' (', poi.quantity, ' ', p.unit, ')') SEPARATOR ', '), 'Tidak ada item') AS items_summary
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            JOIN warehouses w ON po.warehouse_id = w.id
            JOIN users u ON po.created_by = u.id
            LEFT JOIN users app ON po.approved_by = app.id
            LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            LEFT JOIN products p ON poi.product_id = p.id
            GROUP BY po.id, po.po_number, po.supplier_id, po.warehouse_id, po.status, po.total_amount, po.notes, po.created_at, po.created_by, po.approved_by, s.name, w.name, u.name, app.name
            ORDER BY po.id DESC
        ");
        $stmt->execute();
        /** @var list<array{id: int|string, po_number: string, supplier_id: int|string, warehouse_id: int|string, status: string, total_amount: float|string, notes: string|null, created_at: string, created_by: int|string, approved_by: int|string|null, supplier_name: string|null, warehouse_name: string|null, creator_name: string|null, approver_name: string|null, items_summary: string|null}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $orders = [];
        foreach ($rows as $row) {
            $orders[] = new PurchaseOrder(
                (int) $row['id'],
                $row['po_number'],
                (int) $row['supplier_id'],
                (int) $row['warehouse_id'],
                $row['status'],
                (float) $row['total_amount'],
                (int) $row['created_by'],
                $row['approved_by'] !== null ? (int) $row['approved_by'] : null,
                $row['notes'],
                $row['created_at'],
                $row['supplier_name'],
                $row['warehouse_name'],
                $row['creator_name'],
                $row['approver_name'],
                $row['items_summary'] ?? 'Tidak ada item'
            );
        }

        return $orders;
    }

    public function findPurchaseOrderById(int $id): ?PurchaseOrder
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                po.id, po.po_number, po.supplier_id, po.warehouse_id, po.status, po.total_amount, po.notes, po.created_at, po.created_by, po.approved_by,
                s.name AS supplier_name,
                w.name AS warehouse_name,
                u.name AS creator_name,
                app.name AS approver_name,
                COALESCE(GROUP_CONCAT(CONCAT(p.name, ' (', poi.quantity, ' ', p.unit, ')') SEPARATOR ', '), 'Tidak ada item') AS items_summary
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            JOIN warehouses w ON po.warehouse_id = w.id
            JOIN users u ON po.created_by = u.id
            LEFT JOIN users app ON po.approved_by = app.id
            LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            LEFT JOIN products p ON poi.product_id = p.id
            WHERE po.id = ?
            GROUP BY po.id, po.po_number, po.supplier_id, po.warehouse_id, po.status, po.total_amount, po.notes, po.created_at, po.created_by, po.approved_by, s.name, w.name, u.name, app.name
            LIMIT 1
        ");
        $stmt->execute([$id]);
        /** @var array{id: int|string, po_number: string, supplier_id: int|string, warehouse_id: int|string, status: string, total_amount: float|string, notes: string|null, created_at: string, created_by: int|string, approved_by: int|string|null, supplier_name: string|null, warehouse_name: string|null, creator_name: string|null, approver_name: string|null, items_summary: string|null}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new PurchaseOrder(
            (int) $row['id'],
            $row['po_number'],
            (int) $row['supplier_id'],
            (int) $row['warehouse_id'],
            $row['status'],
            (float) $row['total_amount'],
            (int) $row['created_by'],
            $row['approved_by'] !== null ? (int) $row['approved_by'] : null,
            $row['notes'],
            $row['created_at'],
            $row['supplier_name'],
            $row['warehouse_name'],
            $row['creator_name'],
            $row['approver_name'],
            $row['items_summary'] ?? 'Tidak ada item'
        );
    }

    /**
     * @param list<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function createPurchaseOrder(int $supplierId, int $warehouseId, int $createdBy, ?string $notes, array $items): int
    {
        $this->pdo->beginTransaction();
        try {
            $totalAmount = 0.0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            $stmt = $this->pdo->prepare("
                INSERT INTO purchase_orders (po_number, supplier_id, warehouse_id, status, total_amount, created_by, notes, created_at)
                VALUES (?, ?, ?, 'draft', ?, ?, ?, NOW())
            ");
            $stmt->execute([$poNumber, $supplierId, $warehouseId, $totalAmount, $createdBy, $notes]);
            $orderId = (int) $this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare("
                INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, quantity_received, unit_price, subtotal)
                VALUES (?, ?, ?, 0, ?, ?)
            ");
            foreach ($items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price'], $subtotal]);
            }

            $this->pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updatePurchaseOrderStatus(int $orderId, string $status, ?int $approvedBy = null): bool
    {
        if ($approvedBy !== null) {
            $stmt = $this->pdo->prepare("
                UPDATE purchase_orders 
                SET status = ?, approved_by = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$status, $approvedBy, $orderId]);
        }

        $stmt = $this->pdo->prepare("
            UPDATE purchase_orders 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $orderId]);
    }

    /**
     * Memproses Goods Receipt (Penerimaan Barang PO)
     * Menambah ProductStock dan menulis baris StockLedger bertipe Receipt dalam satu transaksi atomik.
     * Mendukung penerimaan sebagian (partial receipt).
     *
     * @param list<array{product_id: int, quantity: int}>|null $itemsReceived
     */
    public function processGoodsReceipt(int $purchaseOrderId, int $warehouseUserId, ?array $itemsReceived = null): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmtPO = $this->pdo->prepare("
                SELECT id, po_number, warehouse_id, status 
                FROM purchase_orders 
                WHERE id = ? 
                LIMIT 1 
                FOR UPDATE
            ");
            $stmtPO->execute([$purchaseOrderId]);
            /** @var array{id: int|string, po_number: string, warehouse_id: int|string, status: string}|false $po */
            $po = $stmtPO->fetch(PDO::FETCH_ASSOC);

            if ($po === false) {
                throw new NotFoundException("Purchase Order #{$purchaseOrderId} tidak ditemukan.");
            }

            $allowedStatuses = ['ordered', 'sent_to_supplier', 'partially_received'];
            if (!in_array($po['status'], $allowedStatuses, true)) {
                throw new ConflictException("Goods Receipt hanya dapat diproses untuk status 'ordered' atau 'partially_received'. Status saat ini: {$po['status']}.");
            }

            $warehouseId = (int) $po['warehouse_id'];
            $poNumber = $po['po_number'];

            // Ambil daftar item PO
            $stmtItems = $this->pdo->prepare("
                SELECT id, product_id, quantity, quantity_received 
                FROM purchase_order_items 
                WHERE purchase_order_id = ?
            ");
            $stmtItems->execute([$purchaseOrderId]);
            /** @var list<array{id: int|string, product_id: int|string, quantity: int|string, quantity_received: int|string}> $poItems */
            $poItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            if (empty($poItems)) {
                throw new ConflictException("Purchase Order tidak memiliki item untuk diterima.");
            }

            $itemsToProcess = [];
            if ($itemsReceived !== null) {
                // Partial receipt: petakan berdasarkan product_id
                $mapReceived = [];
                foreach ($itemsReceived as $rec) {
                    $mapReceived[$rec['product_id']] = $rec['quantity'];
                }
                foreach ($poItems as $pItem) {
                    $pId = (int) $pItem['product_id'];
                    $qtyToRec = $mapReceived[$pId] ?? 0;
                    if ($qtyToRec > 0) {
                        $itemsToProcess[] = [
                            'item_id' => (int) $pItem['id'],
                            'product_id' => $pId,
                            'quantity_to_receive' => $qtyToRec,
                            'total_ordered' => (int) $pItem['quantity'],
                            'previously_received' => (int) $pItem['quantity_received'],
                        ];
                    }
                }
            } else {
                // Full receipt: terima sisa yang belum diterima
                foreach ($poItems as $pItem) {
                    $remaining = (int) $pItem['quantity'] - (int) $pItem['quantity_received'];
                    if ($remaining > 0) {
                        $itemsToProcess[] = [
                            'item_id' => (int) $pItem['id'],
                            'product_id' => (int) $pItem['product_id'],
                            'quantity_to_receive' => $remaining,
                            'total_ordered' => (int) $pItem['quantity'],
                            'previously_received' => (int) $pItem['quantity_received'],
                        ];
                    }
                }
            }

            if (empty($itemsToProcess)) {
                throw new ConflictException("Semua item pada Purchase Order ini sudah diterima sepenuhnya.");
            }

            // Prepared statements
            $stmtLockStock = $this->pdo->prepare("
                SELECT quantity_on_hand 
                FROM inventory_stocks 
                WHERE product_id = ? AND warehouse_id = ? 
                FOR UPDATE
            ");

            $stmtInsertStock = $this->pdo->prepare("
                INSERT INTO inventory_stocks (warehouse_id, product_id, quantity_on_hand, quantity_reserved, quantity_incoming)
                VALUES (?, ?, 0, 0, 0)
                ON DUPLICATE KEY UPDATE quantity_on_hand = quantity_on_hand
            ");

            $stmtAddStock = $this->pdo->prepare("
                UPDATE inventory_stocks 
                SET quantity_on_hand = quantity_on_hand + ? 
                WHERE product_id = ? AND warehouse_id = ?
            ");

            $stmtLedger = $this->pdo->prepare("
                INSERT INTO stock_ledger (
                    warehouse_id, product_id, movement_type, quantity_delta, balance_after,
                    reference_doc_type, reference_doc_number, user_id, notes, created_at
                ) VALUES (?, ?, 'GOODS_RECEIPT', ?, ?, 'PO', ?, ?, ?, NOW())
            ");

            $stmtUpdateItemRec = $this->pdo->prepare("
                UPDATE purchase_order_items 
                SET quantity_received = quantity_received + ? 
                WHERE id = ?
            ");

            foreach ($itemsToProcess as $proc) {
                $pId = $proc['product_id'];
                $qtyRec = $proc['quantity_to_receive'];

                // Pastikan baris stok ada
                $stmtInsertStock->execute([$warehouseId, $pId]);

                // Kunci baris stok
                $stmtLockStock->execute([$pId, $warehouseId]);
                $currStock = (int) ($stmtLockStock->fetchColumn() ?: 0);

                $newBalance = $currStock + $qtyRec;

                // Tambah stok
                $stmtAddStock->execute([$qtyRec, $pId, $warehouseId]);

                // Catat ke ledger
                $notes = "Goods Receipt dari supplier {$poNumber}";
                $stmtLedger->execute([
                    $warehouseId,
                    $pId,
                    $qtyRec,
                    $newBalance,
                    $poNumber,
                    $warehouseUserId,
                    $notes
                ]);

                // Update quantity_received di item PO
                $stmtUpdateItemRec->execute([$qtyRec, $proc['item_id']]);
            }

            // Catat dokumen Goods Receipt
            $grNumber = 'GR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $stmtGR = $this->pdo->prepare("
                INSERT INTO goods_receipts (gr_number, purchase_order_id, warehouse_id, received_by, receipt_date, notes)
                VALUES (?, ?, ?, ?, NOW(), 'Penerimaan barang dari supplier')
            ");
            $stmtGR->execute([$grNumber, $purchaseOrderId, $warehouseId, $warehouseUserId]);
            $grId = (int) $this->pdo->lastInsertId();

            $stmtGRI = $this->pdo->prepare("
                INSERT INTO goods_receipt_items (goods_receipt_id, product_id, quantity_received)
                VALUES (?, ?, ?)
            ");
            foreach ($itemsToProcess as $proc) {
                $stmtGRI->execute([$grId, $proc['product_id'], $proc['quantity_to_receive']]);
            }

            // Tentukan status akhir PO: periksa apakah ada item yang belum selesai
            $stmtCheckRemaining = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM purchase_order_items 
                WHERE purchase_order_id = ? AND quantity_received < quantity
            ");
            $stmtCheckRemaining->execute([$purchaseOrderId]);
            $unreceivedCount = (int) $stmtCheckRemaining->fetchColumn();

            $finalStatus = ($unreceivedCount === 0) ? 'goods_received' : 'partially_received';

            $stmtUpdatePO = $this->pdo->prepare("
                UPDATE purchase_orders 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmtUpdatePO->execute([$finalStatus, $purchaseOrderId]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return list<StockLedger> */
    public function findAllStockLedger(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                sl.id, sl.warehouse_id, sl.product_id, sl.movement_type, sl.quantity_delta, sl.balance_after,
                sl.reference_doc_type, sl.reference_doc_number, sl.user_id, sl.notes, sl.created_at,
                w.name AS warehouse_name,
                p.name AS product_name,
                u.name AS user_name
            FROM stock_ledger sl
            JOIN warehouses w ON sl.warehouse_id = w.id
            JOIN products p ON sl.product_id = p.id
            JOIN users u ON sl.user_id = u.id
            ORDER BY sl.id DESC
            LIMIT 50
        ");
        $stmt->execute();
        /** @var list<array{id: int|string, warehouse_id: int|string, product_id: int|string, movement_type: string, quantity_delta: int|string, balance_after: int|string, reference_doc_type: string, reference_doc_number: string, user_id: int|string, notes: string|null, created_at: string, warehouse_name: string|null, product_name: string|null, user_name: string|null}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ledger = [];
        foreach ($rows as $row) {
            $ledger[] = new StockLedger(
                (int) $row['id'],
                (int) $row['warehouse_id'],
                (int) $row['product_id'],
                $row['movement_type'],
                (int) $row['quantity_delta'],
                (int) $row['balance_after'],
                $row['reference_doc_type'],
                $row['reference_doc_number'],
                (int) $row['user_id'],
                $row['notes'],
                $row['created_at'],
                $row['warehouse_name'],
                $row['product_name'],
                $row['user_name']
            );
        }

        return $ledger;
    }

    /**
     * Kontrak Endpoint API JSON (API-01):
     * GET /api/products/{sku}/availability
     *
     * @return array{sku: string, name: string, unit: string, total_stock: int, warehouses: list<array{warehouse_code: string, warehouse_name: string, quantity: int}>}|null
     */
    public function getProductAvailabilityBySku(string $sku): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, sku, name, unit 
            FROM products 
            WHERE sku = ? AND is_active = 1 
            LIMIT 1
        ");
        $stmt->execute([$sku]);
        /** @var array{id: int|string, sku: string, name: string, unit: string}|false $product */
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product === false) {
            return null;
        }

        $productId = (int) $product['id'];

        $stmtStocks = $this->pdo->prepare("
            SELECT 
                w.code AS warehouse_code, 
                w.name AS warehouse_name, 
                COALESCE(s.quantity_on_hand, 0) AS quantity
            FROM warehouses w
            LEFT JOIN inventory_stocks s ON w.id = s.warehouse_id AND s.product_id = ?
            WHERE w.is_active = 1
            ORDER BY w.id ASC
        ");
        $stmtStocks->execute([$productId]);
        /** @var list<array{warehouse_code: string, warehouse_name: string, quantity: int|string}> $stockRows */
        $stockRows = $stmtStocks->fetchAll(PDO::FETCH_ASSOC);

        $warehouses = [];
        $totalStock = 0;
        foreach ($stockRows as $row) {
            $qty = (int) $row['quantity'];
            $totalStock += $qty;
            $warehouses[] = [
                'warehouse_code' => $row['warehouse_code'],
                'warehouse_name' => $row['warehouse_name'],
                'quantity' => $qty,
            ];
        }

        return [
            'sku' => $product['sku'],
            'name' => $product['name'],
            'unit' => $product['unit'],
            'total_stock' => $totalStock,
            'warehouses' => $warehouses,
        ];
    }
}
