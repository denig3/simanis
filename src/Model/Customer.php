<?php
declare(strict_types=1);

namespace App\Model;

final readonly class Customer
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public ?string $contactPerson = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $address = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'contact_person' => $this->contactPerson ?? '-',
            'phone' => $this->phone ?? '-',
            'email' => $this->email ?? '-',
            'address' => $this->address ?? '-',
        ];
    }
}
