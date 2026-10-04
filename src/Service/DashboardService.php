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
     *     readyGICount: int,
     *     inventoryMetrics: array{
     *         totalCost: float,
     *         totalValue: float,
     *         potentialMargin: float,
     *         marginPercentage: float,
     *         totalUnits: int,
     *         totalSKU: int,
     *         stockJkt: int,
     *         stockSby: int,
     *         stockBdg: int,
     *         totalSORevenue: float,
     *         totalPOExpense: float
     *     },
     *     stockHealth: array{
     *         healthy: int,
     *         warning: int,
     *         danger: int,
     *         outOfStock: int
     *     },
     *     soStats: array<string, array{count: int, amount: float}>,
     *     poStats: array<string, array{count: int, amount: float}>
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

        $soSummary = $this->calculateSoStats($salesOrders);
        $poSummary = $this->calculatePoStats($purchaseOrders);
        $inventoryMetrics = $this->calculateInventoryMetrics($products, $soSummary['revenue'], $poSummary['expense']);
        $stockHealth = $this->calculateStockHealth($products);

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
            'inventoryMetrics' => $inventoryMetrics,
            'stockHealth' => $stockHealth,
            'soStats' => $soSummary['stats'],
            'poStats' => $poSummary['stats'],
        ];
    }

    /**
     * @param list<array<string, mixed>> $products
     * @return array{totalCost: float, totalValue: float, potentialMargin: float, marginPercentage: float, totalUnits: int, totalSKU: int, stockJkt: int, stockSby: int, stockBdg: int, totalSORevenue: float, totalPOExpense: float}
     */
    private function calculateInventoryMetrics(array $products, float $totalSORevenue, float $totalPOExpense): array
    {
        $totalCost = 0.0;
        $totalValue = 0.0;
        $totalUnits = 0;
        $stockJkt = 0;
        $stockSby = 0;
        $stockBdg = 0;

        foreach ($products as $p) {
            $tStock = (int)($p['total_stock'] ?? 0);
            $buyPrice = (float)($p['purchase_price'] ?? 0.0);
            $sellPrice = (float)($p['selling_price'] ?? $p['sell_price'] ?? 0.0);

            $totalUnits += $tStock;
            $totalCost += ($tStock * $buyPrice);
            $totalValue += ($tStock * $sellPrice);

            $stockJkt += (int)($p['stock_jkt'] ?? 0);
            $stockSby += (int)($p['stock_sby'] ?? 0);
            $stockBdg += (int)($p['stock_bdg'] ?? 0);
        }

        $potentialMargin = $totalValue - $totalCost;
        $marginPercentage = $totalCost > 0 ? ($potentialMargin / $totalCost) * 100 : 0.0;

        return [
            'totalCost' => $totalCost,
            'totalValue' => $totalValue,
            'potentialMargin' => $potentialMargin,
            'marginPercentage' => $marginPercentage,
            'totalUnits' => $totalUnits,
            'totalSKU' => count($products),
            'stockJkt' => $stockJkt,
            'stockSby' => $stockSby,
            'stockBdg' => $stockBdg,
            'totalSORevenue' => $totalSORevenue,
            'totalPOExpense' => $totalPOExpense,
        ];
    }

    /**
     * @param list<array<string, mixed>> $products
     * @return array{healthy: int, warning: int, danger: int, outOfStock: int}
     */
    private function calculateStockHealth(array $products): array
    {
        $health = ['healthy' => 0, 'warning' => 0, 'danger' => 0, 'outOfStock' => 0];

        foreach ($products as $p) {
            $tStock = (int)($p['total_stock'] ?? 0);
            $minStock = (int)($p['min_stock_threshold'] ?? 0);

            if ($tStock <= 0) {
                $health['outOfStock']++;
            } elseif ($tStock <= (int)($minStock / 2)) {
                $health['danger']++;
            } elseif ($tStock <= $minStock) {
                $health['warning']++;
            } else {
                $health['healthy']++;
            }
        }

        return $health;
    }

    /**
     * @param list<array<string, mixed>> $salesOrders
     * @return array{stats: array<string, array{count: int, amount: float}>, revenue: float}
     */
    private function calculateSoStats(array $salesOrders): array
    {
        $soStatuses = ['draft', 'pending_approval', 'approved', 'rejected', 'fulfilled', 'cancelled'];
        $soStats = [];
        foreach ($soStatuses as $status) {
            $soStats[$status] = ['count' => 0, 'amount' => 0.0];
        }
        $totalSORevenue = 0.0;
        foreach ($salesOrders as $so) {
            $st = (string)($so['status'] ?? 'draft');
            $amt = (float)($so['total_amount'] ?? 0.0);
            if (!isset($soStats[$st])) {
                $soStats[$st] = ['count' => 0, 'amount' => 0.0];
            }
            $soStats[$st]['count']++;
            $soStats[$st]['amount'] += $amt;

            if (in_array($st, ['approved', 'fulfilled'], true)) {
                $totalSORevenue += $amt;
            }
        }

        return ['stats' => $soStats, 'revenue' => $totalSORevenue];
    }

    /**
     * @param list<array<string, mixed>> $purchaseOrders
     * @return array{stats: array<string, array{count: int, amount: float}>, expense: float}
     */
    private function calculatePoStats(array $purchaseOrders): array
    {
        $poStatuses = ['draft', 'sent_to_supplier', 'received', 'cancelled'];
        $poStats = [];
        foreach ($poStatuses as $status) {
            $poStats[$status] = ['count' => 0, 'amount' => 0.0];
        }
        $totalPOExpense = 0.0;
        foreach ($purchaseOrders as $po) {
            $st = (string)($po['status'] ?? 'draft');
            if ($st === 'goods_received') {
                $st = 'received';
            }
            $amt = (float)($po['total_amount'] ?? 0.0);
            if (!isset($poStats[$st])) {
                $poStats[$st] = ['count' => 0, 'amount' => 0.0];
            }
            $poStats[$st]['count']++;
            $poStats[$st]['amount'] += $amt;

            if (in_array($st, ['received', 'goods_received'], true)) {
                $totalPOExpense += $amt;
            }
        }

        return ['stats' => $poStats, 'expense' => $totalPOExpense];
    }
}
