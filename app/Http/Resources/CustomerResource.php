<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\AddressResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'status' => $this->status,
            
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'default_address' => $this->when(
                $this->relationLoaded('addresses'),
                fn() => new AddressResource($this->addresses->where('is_default', true)->first())
            ),
            
            'stats' => $this->when(
                isset($this->orders_count),
                [
                    'total_orders' => $this->orders_count ?? 0,
                    'total_spent' => $this->total_spent ?? 0,
                    'total_spent_formatted' => '₦' . number_format($this->total_spent ?? 0, 2),
                ]
            ),
            
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}