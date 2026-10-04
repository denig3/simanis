<?php
declare(strict_types=1);

namespace App\Model;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public ?string $description = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description ?? '-',
        ];
    }
}
