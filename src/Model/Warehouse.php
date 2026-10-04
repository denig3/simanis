<?php
declare(strict_types=1);

namespace App\Model;

final readonly class Warehouse
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public string $city,
        public ?string $address = null,
        public int $isActive = 1,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'city' => $this->city,
            'address' => $this->address ?? '-',
            'is_active' => $this->isActive,
            'status_label' => $this->isActive === 1 ? 'Aktif Beroperasi' : 'Nonaktif',
        ];
    }
}
