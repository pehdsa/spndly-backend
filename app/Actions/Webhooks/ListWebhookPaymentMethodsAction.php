<?php

namespace App\Actions\Webhooks;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;

class ListWebhookPaymentMethodsAction
{
    public function handle(): Collection
    {
        return PaymentMethod::query()->orderBy('name')->get();
    }
}
