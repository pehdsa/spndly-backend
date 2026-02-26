<?php

namespace App\Actions\PaymentMethods;

use App\Models\PaymentMethod;

class DeletePaymentMethodAction
{
    public function handle(PaymentMethod $paymentMethod): void
    {
        $paymentMethod->delete();
    }
}
