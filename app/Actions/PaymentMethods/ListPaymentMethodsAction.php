<?php

namespace App\Actions\PaymentMethods;

use App\Models\PaymentMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPaymentMethodsAction
{
    public function handle(): LengthAwarePaginator
    {
        return PaymentMethod::query()->paginateFromRequest(orderBy: 'name', initialDirection: 'asc');
    }
}
