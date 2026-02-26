<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'amount_cents' => $this->amount_cents,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
