<?php

namespace App\Actions\PaymentMethods;

use App\Models\PaymentMethod;

class CreatePaymentMethodAction
{
    public function handle(array $data): PaymentMethod
    {
        $existing = PaymentMethod::query()
            ->onlyTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->first();

        if ($existing) {
            $existing->restore();
            $existing->update($data);

            return $existing->fresh();
        }

        return PaymentMethod::query()->create($data);
    }
}
