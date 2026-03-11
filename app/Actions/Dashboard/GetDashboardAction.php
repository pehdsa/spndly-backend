<?php

namespace App\Actions\Dashboard;

use App\Models\Expense;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class GetDashboardAction
{
    /**
     * @param  array{period?: string, start_date?: string, end_date?: string, user_id?: int}  $filters
     * @return array{
     *     totals: array{total_amount: int, total_count: int},
     *     top_categories: \Illuminate\Support\Collection,
     *     top_payment_methods: \Illuminate\Support\Collection,
     *     recent_expenses: Collection
     * }
     */
    public function handle(User $user, array $filters = []): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $baseQuery = Expense::query()
            ->whereBetween('expenses.created_at', [$startDate, $endDate]);

        $this->applyUserScope($baseQuery, $user, $filters);

        return [
            'totals' => $this->getTotals(clone $baseQuery),
            'top_categories' => $this->getTopCategories(clone $baseQuery),
            'top_payment_methods' => $this->getTopPaymentMethods(clone $baseQuery),
            'recent_expenses' => $this->getRecentExpenses(clone $baseQuery),
        ];
    }

    /**
     * @param  array{period?: string, start_date?: string, end_date?: string}  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveDateRange(array $filters): array
    {
        if (isset($filters['start_date'], $filters['end_date'])) {
            return [
                CarbonImmutable::parse($filters['start_date'])->startOfDay(),
                CarbonImmutable::parse($filters['end_date'])->endOfDay(),
            ];
        }

        $period = $filters['period'] ?? '30d';

        $startDate = match ($period) {
            '7d' => CarbonImmutable::now()->subDays(7)->startOfDay(),
            '90d' => CarbonImmutable::now()->subDays(90)->startOfDay(),
            '12m' => CarbonImmutable::now()->subMonths(12)->startOfDay(),
            default => CarbonImmutable::now()->subDays(30)->startOfDay(),
        };

        return [$startDate, CarbonImmutable::now()->endOfDay()];
    }

    /**
     * @param  array{period?: string, user_id?: int}  $filters
     */
    protected function applyUserScope(Builder $query, User $user, array $filters): void
    {
        if (Gate::forUser($user)->allows('admin')) {
            $query->when(isset($filters['user_id']), fn (Builder $q) => $q->where('expenses.user_id', $filters['user_id']));

            return;
        }

        $query->where('expenses.user_id', $user->id);
    }

    /**
     * @return array{total_amount: int, total_count: int}
     */
    protected function getTotals(Builder $query): array
    {
        $result = $query
            ->selectRaw('COALESCE(SUM(amount_cents), 0) as total_amount, COUNT(*) as total_count')
            ->first();

        return [
            'total_amount' => (int) $result->total_amount,
            'total_count' => (int) $result->total_count,
        ];
    }

    protected function getTopCategories(Builder $query): \Illuminate\Support\Collection
    {
        return $query
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->selectRaw('expenses.category_id, categories.name, SUM(expenses.amount_cents) as total_amount, COUNT(*) as total_count')
            ->groupBy('expenses.category_id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get();
    }

    protected function getTopPaymentMethods(Builder $query): \Illuminate\Support\Collection
    {
        return $query
            ->join('payment_methods', 'expenses.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw('expenses.payment_method_id, payment_methods.name, SUM(expenses.amount_cents) as total_amount, COUNT(*) as total_count')
            ->groupBy('expenses.payment_method_id', 'payment_methods.name')
            ->orderByDesc('total_amount')
            ->get();
    }

    protected function getRecentExpenses(Builder $query): Collection
    {
        return $query
            ->with(['category', 'paymentMethod'])
            ->orderByDesc('expenses.created_at')
            ->limit(5)
            ->get();
    }
}
