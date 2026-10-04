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

        $this->renderDashboard('dashboard.php', [
            'data' => $data,
            'user' => $user,
            'csrf' => $csrf,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function renderDashboard(string $template, array $params): void
    {
        extract($params, EXTR_SKIP);
        require_once dirname(__DIR__, 2) . '/templates/' . $template;
    }
}
