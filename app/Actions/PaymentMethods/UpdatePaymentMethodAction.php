<?php

namespace App\Actions\PaymentMethods;

use App\Models\PaymentMethod;

class UpdatePaymentMethodAction
{
    public function handle(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        $paymentMethod->update($data);

        return $paymentMethod->fresh();
    }
}
