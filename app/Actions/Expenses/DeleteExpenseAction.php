<?php

namespace App\Actions\Expenses;

use App\Models\Expense;

class DeleteExpenseAction
{
    public function handle(Expense $expense): void
    {
        $expense->delete();
    }
}
