<?php
declare(strict_types=1);

namespace App\Model;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public string $role = 'sales',
        public ?int $assignedWarehouseId = null,
        public string $status = 'active',
        public ?string $warehouseName = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => '#USR-' . str_pad((string) $this->id, 2, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'assigned_warehouse_id' => $this->assignedWarehouseId,
            'warehouse' => $this->warehouseName ?? ($this->assignedWarehouseId ? 'Gudang #' . $this->assignedWarehouseId : 'Semua Gudang (Pusat)'),
            'warehouse_name' => $this->warehouseName,
            'status' => $this->status,
            'status_label' => $this->status === 'active' ? 'Aktif' : 'Nonaktif',
        ];
    }
}
