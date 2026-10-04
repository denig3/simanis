<?php
declare(strict_types=1);

namespace App\Model;

final readonly class SalesOrder
{
    public function __construct(
        public int $id,
        public string $soNumber,
        public int $customerId,
        public int $warehouseId,
        public string $status,
        public float $totalAmount,
        public int $createdBy,
        public ?int $approvedBy = null,
        public ?string $notes = null,
        public ?string $createdAt = null,
        public ?string $customerName = null,
        public ?string $warehouseName = null,
        public ?string $creatorName = null,
        public ?string $approverName = null,
        public string $itemsSummary = 'Tidak ada item',
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'so_number' => $this->soNumber,
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName ?? '-',
            'warehouse_id' => $this->warehouseId,
            'warehouse_name' => $this->warehouseName ?? '-',
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'created_by' => $this->createdBy,
            'creator_name' => $this->creatorName ?? '-',
            'approved_by' => $this->approvedBy,
            'approver_name' => $this->approverName,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'items_summary' => $this->itemsSummary,
        ];
    }
}
