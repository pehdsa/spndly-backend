<?php

namespace App\Actions\Expenses;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListExpensesAction
{
    public function handle(User $user): LengthAwarePaginator
    {
        return $user->expenses()
            ->with(['category', 'paymentMethod'])
            ->paginateFromRequest(orderBy: 'created_at', initialDirection: 'desc');
    }
}
