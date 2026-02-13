<?php

namespace App\Dto;

use Storage;
use App\Dto\BaseDto;

readonly class ProductImageDto extends BaseDto
{
    public function __construct(
        public ?int $id,
        public int $productId,
        public string $path,
        public string $url,
        public ?string $thumbnailUrl,
        public int $sortOrder,
        public bool $isPrimary,
        public ?string $altText,
    ) {
    }

    /**
     * Create from request
     */
    public static function fromRequest(array $data, int $productId, int $sortOrder = 0): self
    {
        return new self(
            id: $data['id'] ?? null,
            productId: $productId,
            path: $data['path'],
            url: $data['url'],
            thumbnailUrl: $data['thumbnail_url'] ?? null,
            sortOrder: $sortOrder,
            isPrimary: (bool) ($data['is_primary'] ?? false),
            altText: $data['alt_text'] ?? null,
        );
    }

    /**
     * Create from uploaded file
     */
    public static function fromUploadedFile(
        $file,
        int $productId,
        int $sortOrder = 0,
        bool $isPrimary = false,
        ?string $altText = null
    ): self {
        $path = $file->store('products', 'public');
        $url = Storage::url($path);
        
        return new self(
            id: null,
            productId: $productId,
            path: $path,
            url: $url,
            thumbnailUrl: null,
            sortOrder: $sortOrder,
            isPrimary: $isPrimary,
            altText: $altText,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'path' => $this->path,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnailUrl,
            'sort_order' => $this->sortOrder,
            'is_primary' => $this->isPrimary,
            'alt_text' => $this->altText,
        ];
    }

    /**
     * Get CDN URL (if using CDN)
     */
    public function getCdnUrl(?string $cdnDomain = null): string
    {
        if ($cdnDomain) {
            return str_replace(config('app.url'), $cdnDomain, $this->url);
        }
        
        return $this->url;
    }

    /**
     * Get responsive image URLs
     */
    public function getResponsiveUrls(): array
    {
        return [
            'original' => $this->url,
            'thumbnail' => $this->thumbnailUrl ?? $this->url,
            'medium' => $this->url, 
            'large' => $this->url,
        ];
    }
}

