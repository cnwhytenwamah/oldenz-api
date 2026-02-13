<?php

namespace App\Dto;

use App\Dto\BaseDto;

readonly class CustomerDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $phone,
        public ?string $password,
        public ?string $dateOfBirth,
        public ?string $gender,
        public string $status,
    ) {
    }

    /**
     * Create from request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            password: $data['password'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            gender: $data['gender'] ?? null,
            status: $data['status'] ?? 'active',
        );
    }

    /**
     * Create from model
     */
    public static function fromModel($customer): self
    {
        return new self(
            id: $customer->id,
            firstName: $customer->first_name,
            lastName: $customer->last_name,
            email: $customer->email,
            phone: $customer->phone,
            password: null,
            dateOfBirth: $customer->date_of_birth?->format('Y-m-d'),
            gender: $customer->gender,
            status: $customer->status,
        );
    }

    /**
     * Convert to array for database
     */
    public function toArray(): array
    {
        $data = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->dateOfBirth,
            'gender' => $this->gender,
            'status' => $this->status,
        ];

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        return $data;
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}
