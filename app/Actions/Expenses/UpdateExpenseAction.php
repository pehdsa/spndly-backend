<?php

namespace App\Actions\Expenses;

use App\Models\Expense;

class UpdateExpenseAction
{
    public function handle(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense->fresh(['category', 'paymentMethod']);
    }
}
