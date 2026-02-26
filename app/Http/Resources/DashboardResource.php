<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'totals' => [
                'total_amount' => (int) $this->resource['totals']['total_amount'],
                'total_count' => (int) $this->resource['totals']['total_count'],
            ],
            'top_categories' => $this->resource['top_categories']->map(fn ($item) => [
                'id' => (int) $item->category_id,
                'name' => $item->name,
                'total_amount' => (int) $item->total_amount,
                'total_count' => (int) $item->total_count,
            ])->values(),
            'top_payment_methods' => $this->resource['top_payment_methods']->map(fn ($item) => [
                'id' => (int) $item->payment_method_id,
                'name' => $item->name,
                'total_amount' => (int) $item->total_amount,
                'total_count' => (int) $item->total_count,
            ])->values(),
            'recent_expenses' => ExpenseResource::collection($this->resource['recent_expenses']),
        ];
    }
}
