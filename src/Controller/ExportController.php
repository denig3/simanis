<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ExportService;

final class ExportController extends BaseController
{
    public function __construct(private ExportService $exportService) {}

    /**
     * @param array{id: int|string, name: string, email: string}|null $user
     */
    public function csv(?array $user, string $type, string $delimiter): void
    {
        if ($user === null) {
            header('Location: /login');
            exit;
        }

        $this->exportService->exportCsv($type, $delimiter);
    }
}
