<?php

namespace App\Dto;

use App\Dto\BaseDto;

readonly class AddressDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public int $customerId,
        public string $type,
        public string $firstName,
        public string $lastName,
        public string $phone,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $city,
        public string $state,
        public ?string $postalCode,
        public string $country,
        public bool $isDefault,
    ) {  }

    /**
     * Create from request
     */
    public static function fromRequest(array $data, int $customerId): self
    {
        return new self(
            id: $data['id'] ?? null,
            customerId: $customerId,
            type: $data['type'] ?? 'both',
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            phone: $data['phone'],
            addressLine1: $data['address_line_1'],
            addressLine2: $data['address_line_2'] ?? null,
            city: $data['city'],
            state: $data['state'],
            postalCode: $data['postal_code'] ?? null,
            country: $data['country'] ?? 'Nigeria',
            isDefault: (bool) ($data['is_default'] ?? false),
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'type' => $this->type,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'is_default' => $this->isDefault,
        ];
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddress(): string
    {
        $parts = array_filter([
            $this->addressLine1,
            $this->addressLine2,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}
