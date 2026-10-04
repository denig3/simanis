<?php
declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\DatabaseSeeder;
use App\Service\DashboardService;
use PDO;

final class DashboardController extends BaseController
{
    public function __construct(
        private DashboardService $dashboardService,
        private ?PDO $pdo = null
    ) {}

    /**
     * @param array{id: int|string, name: string, email: string}|null $user
     */
    public function index(?array $user, string $csrf): void
    {
        if ($user === null) {
            header('Location: /login');
            exit;
        }

        if ($this->pdo !== null) {
            DatabaseSeeder::seedIfEmpty($this->pdo);
        }

        $data = $this->dashboardService->getDashboardData();

        $usersList = $data['usersList'];
        $warehousesList = $data['warehousesList'];
        $categoriesList = $data['categoriesList'];
        $suppliersList = $data['suppliersList'];
        $customersList = $data['customersList'];
        $productsStockSummary = $data['productsStockSummary'];
        $salesOrdersList = $data['salesOrdersList'];
        $purchaseOrdersList = $data['purchaseOrdersList'];
        $stockLedgerList = $data['stockLedgerList'];
        $pendingSOCount = $data['pendingSOCount'];
        $waitingPOCount = $data['waitingPOCount'];
        $criticalStockCount = $data['criticalStockCount'];
        $readyGICount = $data['readyGICount'];

        require dirname(__DIR__, 2) . '/templates/dashboard.php';
    }
}
