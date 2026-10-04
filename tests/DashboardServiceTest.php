<?php
declare(strict_types=1);

namespace Tests;

use App\Model\Product;
use App\Model\PurchaseOrder;
use App\Model\SalesOrder;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\CustomerRepositoryInterface;
use App\Repository\OrderRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Repository\SupplierRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Repository\WarehouseRepositoryInterface;
use App\Service\DashboardService;
use PHPUnit\Framework\TestCase;

final class DashboardServiceTest extends TestCase
{
    public function testGetDashboardDataCalculatesMetricsCorrectly(): void
    {
        $userRepo = $this->createStub(UserRepositoryInterface::class);
        $userRepo->method('findAll')->willReturn([]);

        $whRepo = $this->createStub(WarehouseRepositoryInterface::class);
        $whRepo->method('findAll')->willReturn([]);

        $catRepo = $this->createStub(CategoryRepositoryInterface::class);
        $catRepo->method('findAll')->willReturn([]);

        $supRepo = $this->createStub(SupplierRepositoryInterface::class);
        $supRepo->method('findAll')->willReturn([]);

        $custRepo = $this->createStub(CustomerRepositoryInterface::class);
        $custRepo->method('findAll')->willReturn([]);

        $prodRepo = $this->createStub(ProductRepositoryInterface::class);
        $prodRepo->method('findAllWithStock')->willReturn([
            new Product(1, 'P1', 'Product 1', 1, 'unit', 10, 20, 15, 1, 'Cat 1', 5, 0, 0, 5), // critical (5 <= 15)
            new Product(2, 'P2', 'Product 2', 1, 'unit', 10, 20, 5, 1, 'Cat 1', 10, 0, 0, 10), // safe (10 > 5)
        ]);

        $orderRepo = $this->createStub(OrderRepositoryInterface::class);
        $orderRepo->method('findAllSalesOrders')->willReturn([
            new SalesOrder(101, 'SO-1', 1, 1, 'pending_approval', 500.0, 1),
            new SalesOrder(102, 'SO-2', 1, 1, 'approved', 300.0, 1),
        ]);
        $orderRepo->method('findAllPurchaseOrders')->willReturn([
            new PurchaseOrder(201, 'PO-1', 1, 1, 'sent_to_supplier', 1000.0, 1),
        ]);
        $orderRepo->method('findAllStockLedger')->willReturn([]);

        $service = new DashboardService(
            $userRepo,
            $whRepo,
            $catRepo,
            $supRepo,
            $custRepo,
            $prodRepo,
            $orderRepo
        );

        $data = $service->getDashboardData();

        self::assertSame(1, $data['pendingSOCount']);
        self::assertSame(1, $data['waitingPOCount']);
        self::assertSame(1, $data['criticalStockCount']);
        self::assertSame(1, $data['readyGICount']);
        self::assertCount(2, $data['productsStockSummary']);
        self::assertCount(2, $data['salesOrdersList']);
        self::assertCount(1, $data['purchaseOrdersList']);

        // Verify inventory metrics
        self::assertEqualsWithDelta(150.0, $data['inventoryMetrics']['totalCost'], 0.01);
        self::assertEqualsWithDelta(300.0, $data['inventoryMetrics']['totalValue'], 0.01);
        self::assertEqualsWithDelta(150.0, $data['inventoryMetrics']['potentialMargin'], 0.01);
        self::assertEqualsWithDelta(100.0, $data['inventoryMetrics']['marginPercentage'], 0.01);
        self::assertSame(15, $data['inventoryMetrics']['totalUnits']);
        self::assertSame(2, $data['inventoryMetrics']['totalSKU']);
        self::assertSame(15, $data['inventoryMetrics']['stockJkt']);
        self::assertSame(0, $data['inventoryMetrics']['stockSby']);
        self::assertSame(0, $data['inventoryMetrics']['stockBdg']);

        // Verify stock health
        self::assertSame(1, $data['stockHealth']['healthy']);
        self::assertSame(0, $data['stockHealth']['warning']);
        self::assertSame(1, $data['stockHealth']['danger']);
        self::assertSame(0, $data['stockHealth']['outOfStock']);

        // Verify SO & PO stats
        self::assertSame(1, $data['soStats']['pending_approval']['count']);
        self::assertEqualsWithDelta(500.0, $data['soStats']['pending_approval']['amount'], 0.01);
        self::assertSame(1, $data['soStats']['approved']['count']);
        self::assertEqualsWithDelta(300.0, $data['soStats']['approved']['amount'], 0.01);
        self::assertSame(1, $data['poStats']['sent_to_supplier']['count']);
        self::assertEqualsWithDelta(1000.0, $data['poStats']['sent_to_supplier']['amount'], 0.01);
    }
}
