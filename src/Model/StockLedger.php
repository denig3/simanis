<?php
declare(strict_types=1);

namespace App\Model;

final readonly class StockLedger
{
    public function __construct(
        public int $id,
        public int $warehouseId,
        public int $productId,
        public string $movementType,
        public int $quantityDelta,
        public int $balanceAfter,
        public ?string $referenceDocType = null,
        public ?string $referenceDocNumber = null,
        public ?int $userId = null,
        public ?string $notes = null,
        public ?string $createdAt = null,
        public ?string $warehouseName = null,
        public ?string $productSku = null,
        public ?string $productName = null,
        public ?string $unit = null,
        public ?string $userName = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouseId,
            'warehouse_name' => $this->warehouseName ?? '-',
            'product_id' => $this->productId,
            'product_sku' => $this->productSku ?? '-',
            'sku' => $this->productSku ?? '-',
            'product_name' => $this->productName ?? '-',
            'unit' => $this->unit ?? 'unit',
            'movement_type' => $this->movementType,
            'quantity_delta' => $this->quantityDelta,
            'balance_after' => $this->balanceAfter,
            'reference_doc_type' => $this->referenceDocType,
            'reference_doc_number' => $this->referenceDocNumber,
            'user_id' => $this->userId,
            'user_name' => $this->userName ?? '-',
            'notes' => $this->notes ?? '-',
            'created_at' => $this->createdAt,
        ];
    }
}
