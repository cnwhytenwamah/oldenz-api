<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'path' => $this->path,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,
            'alt_text' => $this->alt_text,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
