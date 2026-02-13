<?php

namespace App\Dto;

use Str;
use App\Dto\BaseDto;

readonly class CategoryDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public ?int $parentId,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $image,
        public int $sortOrder,
        public bool $isActive,
        public bool $isFeatured,
        public ?array $meta,
    ) { }

    /**
     * Create from request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            parentId: $data['parent_id'] ?? null,
            name: $data['name'],
            slug: $data['slug'] ?? Str::slug($data['name']),
            description: $data['description'] ?? null,
            image: $data['image'] ?? null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
            isFeatured: (bool) ($data['is_featured'] ?? false),
            meta: $data['meta'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
            'is_featured' => $this->isFeatured,
            'meta' => $this->meta,
        ];
    }
}