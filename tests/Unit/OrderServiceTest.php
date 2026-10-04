<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\InsufficientStockException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\InMemoryOrderRepository;
use App\Service\OrderService;
use PHPUnit\Framework\TestCase;

/**
 * TEST-01: UNIT TEST TERISOLASI (ZERO DATABASE / IN-MEMORY FAKE)
 *
 * Menguji logika bisnis tanpa menyentuh database sungguhan, PDO, atau session.
 * Memenuhi ARCH-01 (Dependency Inversion dengan InMemory Fake Repository).
 */
final class OrderServiceTest extends TestCase
{
    private InMemoryOrderRepository $repository;
    private OrderService $service;

    protected function setUp(): void
    {
        $this->repository = new InMemoryOrderRepository();
        $this->service = new OrderService($this->repository);
    }

    // =========================================================================
    // AREA 1: SEGREGATION OF DUTIES (SOD) & AUTHORIZATION LOGIC
    // =========================================================================

    public function testSalesCannotApproveSalesOrder(): void
    {
        $salesUser = ['id' => 2, 'role' => 'sales'];
        $soId = $this->repository->createSalesOrder(1, 1, 2, 'Draft order', [
            ['product_id' => 1, 'quantity' => 2, 'unit_price' => 15000000.0],
        ]);
        $this->service->submitSalesOrder($salesUser, $soId);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Hanya Admin yang berwenang');

        $this->service->approveSalesOrder($salesUser, $soId);
    }

    public function testCreatorCannotApproveOwnSalesOrderEvenIfAdmin(): void
    {
        $adminCreator = ['id' => 1, 'role' => 'admin'];
        $soId = $this->repository->createSalesOrder(1, 1, 1, 'Admin order', [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 15000000.0],
        ]);
        $this->service->submitSalesOrder($adminCreator, $soId);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Pelanggaran Segregation of Duties: Pembuat order dilarang menyetujui order miliknya sendiri');

        // Admin 1 mencoba menyetujui order buatannya sendiri (dilarang!)
        $this->service->approveSalesOrder($adminCreator, $soId);
    }

    public function testIndependentAdminCanApproveSalesOrderCreatedBySales(): void
    {
        $salesUser = ['id' => 2, 'role' => 'sales'];
        $independentAdmin = ['id' => 1, 'role' => 'admin'];

        $soId = $this->repository->createSalesOrder(1, 1, 2, 'Sales order', [
            ['product_id' => 1, 'quantity' => 2, 'unit_price' => 15000000.0],
        ]);
        $this->service->submitSalesOrder($salesUser, $soId);

        $this->service->approveSalesOrder($independentAdmin, $soId);

        $so = $this->service->getSalesOrder($soId);
        $this->assertSame('approved', $so->status);
        $this->assertSame(1, $so->approvedBy);
    }

    // =========================================================================
    // AREA 2: STATUS TRANSITIONS & VALIDATION
    // =========================================================================

    public function testCannotSubmitNonDraftSalesOrder(): void
    {
        $admin = ['id' => 1, 'role' => 'admin'];
        $sales = ['id' => 2, 'role' => 'sales'];

        $soId = $this->repository->createSalesOrder(1, 1, 2, 'Order', [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 15000000.0],
        ]);
        $this->service->submitSalesOrder($sales, $soId);
        $this->service->approveSalesOrder($admin, $soId);

        // Sudah approved, tidak boleh di-submit ulang
        $this->expectException(ConflictException::class);
        $this->service->submitSalesOrder($sales, $soId);
    }

    public function testCannotFulfillUnapprovedSalesOrder(): void
    {
        $warehouseUser = ['id' => 4, 'role' => 'warehouse'];
        $soId = $this->repository->createSalesOrder(1, 1, 2, 'Draft order', [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 15000000.0],
        ]);

        $this->expectException(ConflictException::class);
        $this->service->fulfillSalesOrder($warehouseUser, $soId);
    }

    public function testCannotCancelAlreadyReceivedPurchaseOrder(): void
    {
        $warehouseUser = ['id' => 4, 'role' => 'warehouse'];
        $poId = $this->repository->createPurchaseOrder(1, 1, 1, 'PO restock', [
            ['product_id' => 1, 'quantity' => 10, 'unit_price' => 12000000.0],
        ]);
        $this->service->orderPurchaseOrder($warehouseUser, $poId);
        $this->service->receivePurchaseOrder($warehouseUser, $poId);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('sudah diterima tidak dapat dibatalkan');

        $this->service->cancelPurchaseOrder($warehouseUser, $poId);
    }

    // =========================================================================
    // AREA 3: STOCK LOGIC, MUTATION & CONCURRENCY GUARDS
    // =========================================================================

    public function testGoodsIssueRejectedWhenStockIsInsufficient(): void
    {
        $admin = ['id' => 1, 'role' => 'admin'];
        $sales = ['id' => 2, 'role' => 'sales'];
        $warehouseUser = ['id' => 4, 'role' => 'warehouse'];

        // Stok produk 1 di gudang 1 hanya ada 3
        $this->repository->stocks['1:1'] = 3;

        // Order meminta 5 unit
        $soId = $this->repository->createSalesOrder(1, 1, 2, 'Order over capacity', [
            ['product_id' => 1, 'quantity' => 5, 'unit_price' => 15000000.0],
        ]);
        $this->service->submitSalesOrder($sales, $soId);
        $this->service->approveSalesOrder($admin, $soId);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Stok tidak mencukupi');

        $this->service->fulfillSalesOrder($warehouseUser, $soId);
    }

    public function testGoodsIssueSuccessDecrementsStockAndWritesImmutableLedger(): void
    {
        $admin = ['id' => 1, 'role' => 'admin'];
        $sales = ['id' => 2, 'role' => 'sales'];
        $warehouseUser = ['id' => 4, 'role' => 'warehouse'];

        // Stok awal 10
        $this->repository->stocks['1:1'] = 10;

        $soId = $this->repository->createSalesOrder(1, 1, 2, 'Order valid', [
            ['product_id' => 1, 'quantity' => 4, 'unit_price' => 15000000.0],
        ]);
        $this->service->submitSalesOrder($sales, $soId);
        $this->service->approveSalesOrder($admin, $soId);

        $this->service->fulfillSalesOrder($warehouseUser, $soId);

        // Verifikasi stok berkurang dari 10 menjadi 6
        $this->assertSame(6, $this->repository->stocks['1:1']);

        // Verifikasi audit trail di StockLedger
        $ledgers = $this->repository->findAllStockLedger();
        $this->assertCount(1, $ledgers);
        $lastLedger = $ledgers[0];
        $this->assertSame('GOODS_ISSUE', $lastLedger->movementType);
        $this->assertSame(-4, $lastLedger->quantityDelta);
        $this->assertSame(6, $lastLedger->balanceAfter);
        $this->assertSame('SO', $lastLedger->referenceDocType);
    }

    public function testGoodsReceiptIncrementsStockAndWritesImmutableLedger(): void
    {
        $warehouseUser = ['id' => 4, 'role' => 'warehouse'];

        // Stok awal 5
        $this->repository->stocks['1:2'] = 5;

        $poId = $this->repository->createPurchaseOrder(1, 1, 4, 'PO Monitor', [
            ['product_id' => 2, 'quantity' => 15, 'unit_price' => 3200000.0],
        ]);
        $this->service->orderPurchaseOrder($warehouseUser, $poId);

        $this->service->receivePurchaseOrder($warehouseUser, $poId);

        // Stok bertambah dari 5 menjadi 20
        $this->assertSame(20, $this->repository->stocks['1:2']);

        // Ledger mencatat GOODS_RECEIPT dengan delta +15
        $ledgers = $this->repository->findAllStockLedger();
        $this->assertCount(1, $ledgers);
        $this->assertSame('GOODS_RECEIPT', $ledgers[0]->movementType);
        $this->assertSame(15, $ledgers[0]->quantityDelta);
        $this->assertSame(20, $ledgers[0]->balanceAfter);
    }

    // =========================================================================
    // AREA 4: JSON API AVAILABILITY
    // =========================================================================

    public function testGetProductAvailabilityThrowsNotFoundForNonExistentSku(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->getProductAvailability('NONEXISTENT');
    }

    public function testGetProductAvailabilityReturnsCorrectStructure(): void
    {
        $result = $this->service->getProductAvailability('PRD-001');

        $this->assertSame('PRD-001', $result['sku']);
        $this->assertSame(50, $result['total_stock']);
        $this->assertCount(2, $result['warehouses']);
    }
}
