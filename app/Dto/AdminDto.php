<?php

namespace App\Dto;

readonly class AdminDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $password,
        public string $role,
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
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            password: $data['password'] ?? null,
            role: $data['role'] ?? 'admin',
            status: $data['status'] ?? 'active',
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
        ];

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        return $data;
    }

    /**
     * Check if admin is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if admin is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

