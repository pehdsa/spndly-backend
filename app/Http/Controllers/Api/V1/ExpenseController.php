<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Expenses\CreateExpenseAction;
use App\Actions\Expenses\DeleteExpenseAction;
use App\Actions\Expenses\ListExpensesAction;
use App\Actions\Expenses\UpdateExpenseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\MessageResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    public function index(Request $request, ListExpensesAction $action): AnonymousResourceCollection
    {
        return ExpenseResource::collection($action->handle($request->user()));
    }

    public function show(Expense $expense): ExpenseResource
    {
        Gate::authorize('view', $expense);

        return new ExpenseResource($expense->load(['category', 'paymentMethod']));
    }

    public function store(StoreExpenseRequest $request, CreateExpenseAction $action): JsonResponse
    {
        $expense = $action->handle($request->user(), $request->validated());

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, UpdateExpenseAction $action): ExpenseResource
    {
        Gate::authorize('update', $expense);

        return new ExpenseResource($action->handle($expense, $request->validated()));
    }

    public function destroy(Expense $expense, DeleteExpenseAction $action): MessageResource
    {
        Gate::authorize('delete', $expense);

        $action->handle($expense);

        return new MessageResource('Expense deleted successfully.');
    }
}
