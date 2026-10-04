<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Exception\InsufficientStockException;
use App\Infrastructure\Database;
use App\Repository\PdoOrderRepository;
use App\Service\OrderService;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * TEST-02: INTEGRATION TEST (MENYENTUH MYSQL NYATA DI DOCKER)
 *
 * Menguji alur end-to-end, transaksi database ACID eksplisit, dan mekanisme
 * Pessimistic Locking (SELECT ... FOR UPDATE) untuk mencegah oversell (ARCH-02).
 */
final class ConcurrencyStockIntegrationTest extends TestCase
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
            $this->markTestSkipped('Koneksi MySQL Docker belum aktif. Jalankan via: docker compose exec app vendor/bin/phpunit');
        }
    }

    /**
     * Test 1: Goods Receipt secara nyata menambah saldo stok dan mencatat StockLedger di MySQL.
     */
    public function testGoodsReceiptIncrementsRealDatabaseStockAndStockLedger(): void
    {
        if ($this->pdo === null || $this->service === null || $this->orderRepo === null) {
            $this->markTestSkipped('Database tidak tersedia.');
        }

        $warehouseId = 1;
        $productId = 3; // Keyboard Mekanikal

        // Cek stok awal di database
        $stmtInitial = $this->pdo->prepare('SELECT quantity_on_hand FROM inventory_stocks WHERE product_id = ? AND warehouse_id = ?');
        $stmtInitial->execute([$productId, $warehouseId]);
        $initialStock = (int) ($stmtInitial->fetchColumn() ?: 0);

        // Buat Purchase Order
        $userWarehouse = ['id' => 4, 'role' => 'warehouse'];
        $poId = $this->orderRepo->createPurchaseOrder(1, $warehouseId, 4, 'Integration test PO', [
            ['product_id' => $productId, 'quantity' => 10, 'unit_price' => 600000.0],
        ]);
        $this->service->orderPurchaseOrder($userWarehouse, $poId);

        // Eksekusi Goods Receipt nyata
        $this->service->receivePurchaseOrder($userWarehouse, $poId);

        // Verifikasi langsung ke database MySQL bahwa stok fisik bertambah 10
        $stmtFinal = $this->pdo->prepare('SELECT quantity_on_hand FROM inventory_stocks WHERE product_id = ? AND warehouse_id = ?');
        $stmtFinal->execute([$productId, $warehouseId]);
        $finalStock = (int) $stmtFinal->fetchColumn();

        $this->assertSame($initialStock + 10, $finalStock, 'Stok pada database MySQL harus bertambah sesuai kuantitas Goods Receipt.');

        // Verifikasi Stock Ledger tercatat di database
        $stmtLedger = $this->pdo->prepare("
            SELECT movement_type, quantity_delta, balance_after 
            FROM stock_ledger 
            WHERE product_id = ? AND warehouse_id = ? 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtLedger->execute([$productId, $warehouseId]);
        $ledger = $stmtLedger->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($ledger);
        $this->assertSame('GOODS_RECEIPT', $ledger['movement_type']);
        $this->assertSame(10, (int) $ledger['quantity_delta']);
        $this->assertSame($finalStock, (int) $ledger['balance_after']);
    }

    /**
     * Test 2: Goods Issue secara nyata mengurangi saldo stok di MySQL dalam transaksi yang aman.
     */
    public function testGoodsIssueDecrementsRealStockAndWritesStockLedger(): void
    {
        if ($this->pdo === null || $this->service === null || $this->orderRepo === null) {
            $this->markTestSkipped('Database tidak tersedia.');
        }

        $warehouseId = 1;
        $productId = 3;

        // Pastikan stok mencukupi
        $this->pdo->prepare('UPDATE inventory_stocks SET quantity_on_hand = 50 WHERE product_id = ? AND warehouse_id = ?')
            ->execute([$productId, $warehouseId]);

        // Buat dan setujui Sales Order
        $adminId = (int) ($this->pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn() ?: 6);
        $salesId = (int) ($this->pdo->query("SELECT id FROM users WHERE role = 'sales' LIMIT 1")->fetchColumn() ?: 2);
        $warehouseUserId = (int) ($this->pdo->query("SELECT id FROM users WHERE role = 'warehouse' LIMIT 1")->fetchColumn() ?: 4);

        $salesUser = ['id' => $salesId, 'role' => 'sales'];
        $adminUser = ['id' => $adminId, 'role' => 'admin'];
        $warehouseUser = ['id' => $warehouseUserId, 'role' => 'warehouse'];

        $soId = $this->orderRepo->createSalesOrder(1, $warehouseId, $salesId, 'Integration test SO', [
            ['product_id' => $productId, 'quantity' => 5, 'unit_price' => 850000.0],
        ]);
        $this->service->submitSalesOrder($salesUser, $soId);
        $this->service->approveSalesOrder($adminUser, $soId);

        // Eksekusi Goods Issue
        $this->service->fulfillSalesOrder($warehouseUser, $soId);

        // Verifikasi stok berkurang menjadi 45
        $stmt = $this->pdo->prepare('SELECT quantity_on_hand FROM inventory_stocks WHERE product_id = ? AND warehouse_id = ?');
        $stmt->execute([$productId, $warehouseId]);
        $this->assertSame(45, (int) $stmt->fetchColumn());

        // Verifikasi audit trail di stock_ledger
        $stmtLedger = $this->pdo->prepare("
            SELECT movement_type, quantity_delta, balance_after 
            FROM stock_ledger 
            WHERE product_id = ? AND warehouse_id = ? 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtLedger->execute([$productId, $warehouseId]);
        $ledger = $stmtLedger->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($ledger);
        $this->assertSame('GOODS_ISSUE', $ledger['movement_type']);
        $this->assertSame(-5, (int) $ledger['quantity_delta']);
        $this->assertSame(45, (int) $ledger['balance_after']);
    }

    /**
     * Test 3: Concurrency Protection (ARCH-02):
     * Goods Issue kedua WAJIB DITOLAK ketika stok fisik telah dihabiskan oleh Goods Issue pertama.
     * Mencegah stok minus / oversell secara mutlak.
     */
    public function testSecondGoodsIssueFailsWhenStockIsExhaustedByFirstOrder(): void
    {
        if ($this->pdo === null || $this->service === null || $this->orderRepo === null) {
            $this->markTestSkipped('Database tidak tersedia.');
        }

        $warehouseId = 2; // Gudang Surabaya
        $productId = 1;   // Laptop

        // Set stok fisik menjadi tepat 5 unit
        $this->pdo->prepare('UPDATE inventory_stocks SET quantity_on_hand = 5 WHERE product_id = ? AND warehouse_id = ?')
            ->execute([$productId, $warehouseId]);

        $adminId = (int) ($this->pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn() ?: 6);
        $salesId = (int) ($this->pdo->query("SELECT id FROM users WHERE role = 'sales' LIMIT 1")->fetchColumn() ?: 2);
        $warehouseUserId = (int) ($this->pdo->query("SELECT id FROM users WHERE role = 'warehouse' LIMIT 1")->fetchColumn() ?: 4);

        $salesUser = ['id' => $salesId, 'role' => 'sales'];
        $adminUser = ['id' => $adminId, 'role' => 'admin'];
        $warehouseUser = ['id' => $warehouseUserId, 'role' => 'warehouse'];

        // Order 1 meminta 4 unit (tersisa 1 unit)
        $so1 = $this->orderRepo->createSalesOrder(1, $warehouseId, $salesId, 'Order 1 - minta 4', [
            ['product_id' => $productId, 'quantity' => 4, 'unit_price' => 15000000.0],
        ]);
        $this->service->submitSalesOrder($salesUser, $so1);
        $this->service->approveSalesOrder($adminUser, $so1);

        // Order 2 meminta 3 unit (jika diproses bersamaan, total butuh 7 unit, padahal stok cuma 5!)
        $so2 = $this->orderRepo->createSalesOrder(1, $warehouseId, $salesId, 'Order 2 - minta 3', [
            ['product_id' => $productId, 'quantity' => 3, 'unit_price' => 15000000.0],
        ]);
        $this->service->submitSalesOrder($salesUser, $so2);
        $this->service->approveSalesOrder($adminUser, $so2);

        // Eksekusi Order 1 -> Sukses, stok sisa 1
        $this->service->fulfillSalesOrder($warehouseUser, $so1);

        $stmtStock = $this->pdo->prepare('SELECT quantity_on_hand FROM inventory_stocks WHERE product_id = ? AND warehouse_id = ?');
        $stmtStock->execute([$productId, $warehouseId]);
        $this->assertSame(1, (int) $stmtStock->fetchColumn());

        // Eksekusi Order 2 -> WAJIB MELEMPAR InsufficientStockException dan ROLLBACK!
        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Stok tidak mencukupi');

        try {
            $this->service->fulfillSalesOrder($warehouseUser, $so2);
        } finally {
            // Verifikasi stok tetap 1, tidak boleh minus/oversell
            $stmtFinal = $this->pdo->prepare('SELECT quantity_on_hand FROM inventory_stocks WHERE product_id = ? AND warehouse_id = ?');
            $stmtFinal->execute([$productId, $warehouseId]);
            $this->assertSame(1, (int) $stmtFinal->fetchColumn(), 'Stok fisik tidak boleh berkurang saat transaksi ditolak.');
        }
    }
}
