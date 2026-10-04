<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Database;
use App\Repository\PdoOrderRepository;
use App\Service\OrderService;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

final class PurchaseOrderWorkflowIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ?OrderService $service = null;
    private ?PdoOrderRepository $orderRepo = null;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::connect();
            $this->orderRepo = new PdoOrderRepository($this->pdo);
            $this->service = new OrderService($this->orderRepo);
        } catch (Throwable) {
            $this->markTestSkipped('Koneksi MySQL Docker belum aktif.');
        }
    }

    public function testCreatePurchaseOrderAsDraftThenOrderThenReceiveGoods(): void
    {
        if ($this->pdo === null || $this->service === null || $this->orderRepo === null) {
            $this->markTestSkipped('Database tidak tersedia.');
        }

        $warehouseId = 1;
        $productId = 2; // Monitor 27 Inch
        $supplierId = 1;

        $adminId = (int) ($this->pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn() ?: 6);
        $warehouseUserId = (int) ($this->pdo->query("SELECT id FROM users WHERE role = 'warehouse' LIMIT 1")->fetchColumn() ?: 4);

        $adminUser = ['id' => $adminId, 'role' => 'admin'];
        $warehouseUser = ['id' => $warehouseUserId, 'role' => 'warehouse'];

        // 1. Simpan stok awal
        $stmtInitial = $this->pdo->prepare('SELECT quantity_on_hand FROM inventory_stocks WHERE product_id = ? AND warehouse_id = ?');
        $stmtInitial->execute([$productId, $warehouseId]);
        $initialStock = (int) ($stmtInitial->fetchColumn() ?: 0);

        // 2. Buat PO sebagai DRAFT
        $poId = $this->service->createPurchaseOrder($warehouseUser, [
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'notes' => 'Pengadaan stok baru test',
            'order_immediately' => false,
            'items' => [
                ['product_id' => $productId, 'quantity' => 12, 'unit_price' => 3200000.0],
            ],
        ]);

        $this->assertGreaterThan(0, $poId);

        // Verifikasi detail PO status draft
        $po = $this->service->getPurchaseOrder($poId);
        $this->assertNotNull($po);
        $this->assertSame('draft', $po->status);

        // Verifikasi item PO
        $items = $this->service->getPurchaseOrderItems($poId);
        $this->assertCount(1, $items);
        $this->assertSame(12, $items[0]['quantity']);
        $this->assertSame(0, $items[0]['quantity_received']);

        // 3. Terbitkan PO ke supplier (order)
        $this->service->orderPurchaseOrder($warehouseUser, $poId);
        $poAfterOrder = $this->service->getPurchaseOrder($poId);
        $this->assertNotNull($poAfterOrder);
        $this->assertSame('sent_to_supplier', $poAfterOrder->status);

        // 4. Catat Goods Receipt (GR)
        $this->service->receivePurchaseOrder($warehouseUser, $poId);

        // Verifikasi status PO berubah menjadi goods_received
        $poAfterGR = $this->service->getPurchaseOrder($poId);
        $this->assertNotNull($poAfterGR);
        $this->assertSame('goods_received', $poAfterGR->status);

        // Verifikasi item quantity_received terupdate
        $itemsAfterGR = $this->service->getPurchaseOrderItems($poId);
        $this->assertSame(12, $itemsAfterGR[0]['quantity_received']);

        // Verifikasi stok fisik bertambah tepat 12 di MySQL
        $stmtFinal = $this->pdo->prepare('SELECT quantity_on_hand FROM inventory_stocks WHERE product_id = ? AND warehouse_id = ?');
        $stmtFinal->execute([$productId, $warehouseId]);
        $finalStock = (int) $stmtFinal->fetchColumn();
        $this->assertSame($initialStock + 12, $finalStock);

        // Verifikasi audit trail di stock_ledger
        $stmtLedger = $this->pdo->prepare("
            SELECT movement_type, quantity_delta, balance_after 
            FROM stock_ledger 
            WHERE product_id = ? AND warehouse_id = ? 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtLedger->execute([$productId, $warehouseId]);
        $ledger = $stmtLedger->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($ledger);
        $this->assertSame('GOODS_RECEIPT', $ledger['movement_type']);
        $this->assertSame(12, (int) $ledger['quantity_delta']);
        $this->assertSame($finalStock, (int) $ledger['balance_after']);
    }

    public function testCancelPurchaseOrder(): void
    {
        if ($this->pdo === null || $this->service === null || $this->orderRepo === null) {
            $this->markTestSkipped('Database tidak tersedia.');
        }

        $adminId = (int) ($this->pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn() ?: 6);
        $adminUser = ['id' => $adminId, 'role' => 'admin'];

        $poId = $this->service->createPurchaseOrder($adminUser, [
            'supplier_id' => 1,
            'warehouse_id' => 1,
            'notes' => 'PO to cancel',
            'order_immediately' => false,
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'unit_price' => 12000000.0],
            ],
        ]);

        $this->service->cancelPurchaseOrder($adminUser, $poId);
        $po = $this->service->getPurchaseOrder($poId);
        $this->assertNotNull($po);
        $this->assertSame('cancelled', $po->status);
    }
}
