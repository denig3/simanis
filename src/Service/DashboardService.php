<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\CategoryRepositoryInterface;
use App\Repository\CustomerRepositoryInterface;
use App\Repository\OrderRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Repository\SupplierRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Repository\WarehouseRepositoryInterface;

final class DashboardService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private WarehouseRepositoryInterface $warehouseRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private SupplierRepositoryInterface $supplierRepository,
        private CustomerRepositoryInterface $customerRepository,
        private ProductRepositoryInterface $productRepository,
        private OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * @return array{
     *     usersList: list<array<string, mixed>>,
     *     warehousesList: list<array<string, mixed>>,
     *     categoriesList: list<array<string, mixed>>,
     *     suppliersList: list<array<string, mixed>>,
     *     customersList: list<array<string, mixed>>,
     *     productsStockSummary: list<array<string, mixed>>,
     *     salesOrdersList: list<array<string, mixed>>,
     *     purchaseOrdersList: list<array<string, mixed>>,
     *     stockLedgerList: list<array<string, mixed>>,
     *     pendingSOCount: int,
     *     waitingPOCount: int,
     *     criticalStockCount: int,
     *     readyGICount: int
     * }
     */
    public function getDashboardData(): array
    {
        $users = array_map(static fn($u) => $u->toArray(), $this->userRepository->findAll());
        $warehouses = array_map(static fn($w) => $w->toArray(), $this->warehouseRepository->findAll());
        $categories = array_map(static fn($c) => $c->toArray(), $this->categoryRepository->findAll());
        $suppliers = array_map(static fn($s) => $s->toArray(), $this->supplierRepository->findAll());
        $customers = array_map(static fn($c) => $c->toArray(), $this->customerRepository->findAll());
        $products = array_map(static fn($p) => $p->toArray(), $this->productRepository->findAllWithStock());
        $salesOrders = array_map(static fn($so) => $so->toArray(), $this->orderRepository->findAllSalesOrders());
        $purchaseOrders = array_map(static fn($po) => $po->toArray(), $this->orderRepository->findAllPurchaseOrders());
        $stockLedgers = array_map(static fn($sl) => $sl->toArray(), $this->orderRepository->findAllStockLedger());

        $pendingSOCount = count(array_filter($salesOrders, static fn($so) => ($so['status'] ?? '') === 'pending_approval'));
        $waitingPOCount = count(array_filter($purchaseOrders, static fn($po) => ($po['status'] ?? '') === 'sent_to_supplier'));
        $criticalStockCount = count(array_filter($products, static fn($p) => (int)($p['total_stock'] ?? 0) <= (int)($p['min_stock_threshold'] ?? 0)));
        $readyGICount = count(array_filter($salesOrders, static fn($so) => ($so['status'] ?? '') === 'approved'));

        return [
            'usersList' => $users,
            'warehousesList' => $warehouses,
            'categoriesList' => $categories,
            'suppliersList' => $suppliers,
            'customersList' => $customers,
            'productsStockSummary' => $products,
            'salesOrdersList' => $salesOrders,
            'purchaseOrdersList' => $purchaseOrders,
            'stockLedgerList' => $stockLedgers,
            'pendingSOCount' => $pendingSOCount,
            'waitingPOCount' => $waitingPOCount,
            'criticalStockCount' => $criticalStockCount,
            'readyGICount' => $readyGICount,
        ];
    }
}
