<?php

namespace App\Actions\Webhooks;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class ListWebhookExpensesAction
{
    /**
     * @param  array{period?: string, start_date?: string, end_date?: string}  $filters
     */
    public function handle(User $user, array $filters = []): Collection
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        return $user->expenses()
            ->with(['category', 'paymentMethod'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get();
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
}
