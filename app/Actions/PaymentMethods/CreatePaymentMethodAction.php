<?php

namespace App\Actions\PaymentMethods;

use App\Models\PaymentMethod;

class CreatePaymentMethodAction
{
    public function handle(array $data): PaymentMethod
    {
        return PaymentMethod::query()->create($data);
    }
}
