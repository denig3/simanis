<?php
declare(strict_types=1);

namespace App\Model;

final readonly class Product
{
    public function __construct(
        public int $id,
        public string $sku,
        public string $name,
        public int $categoryId,
        public string $unit = 'unit',
        public float $purchasePrice = 0.0,
        public float $sellingPrice = 0.0,
        public int $minStockThreshold = 10,
        public int $isActive = 1,
        public ?string $categoryName = null,
        public int $stockJkt = 0,
        public int $stockSby = 0,
        public int $stockBdg = 0,
        public int $totalStock = 0,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName ?? '-',
            'unit' => $this->unit,
            'purchase_price' => $this->purchasePrice,
            'selling_price' => $this->sellingPrice,
            'min_stock_threshold' => $this->minStockThreshold,
            'is_active' => $this->isActive,
            'stock_jkt' => $this->stockJkt,
            'stock_sby' => $this->stockSby,
            'stock_bdg' => $this->stockBdg,
            'total_stock' => $this->totalStock,
        ];
    }
}
