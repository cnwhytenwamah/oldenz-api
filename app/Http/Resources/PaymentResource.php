<?php

namespace App\Http\Resources;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'order_id' => $this->order_id,
            'transaction_reference' => $this->transaction_reference,
            'payment_gateway' => $this->payment_gateway,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            
            'amount' => $this->amount,
            'amount_formatted' => '₦' . number_format($this->amount, 2),
            'currency' => $this->currency,

            'gateway_reference' => $this->gateway_reference,
            'gateway_response' => $this->when(
                $request->user() instanceof Admin,
                $this->gateway_response
            ),
            
            'card' => $this->when(
                $this->card_type || $this->card_last_four,
                [
                    'type' => $this->card_type,
                    'last_four' => $this->card_last_four,
                    'bank' => $this->bank_name,
                ]
            ),
            
            'paid_at' => $this->paid_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
