<?php

namespace App\Actions\Expenses;

use App\Models\Expense;
use App\Models\User;

class CreateExpenseAction
{
    public function handle(User $user, array $data): Expense
    {
        $expense = $user->expenses()->create($data);

        return $expense->load(['category', 'paymentMethod']);
    }
}
