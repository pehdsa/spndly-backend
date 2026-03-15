<?php

namespace App\Actions\Webhooks;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class ListWebhookExpensesAction
{
    /**
     * @param  array{period?: string, start_date?: string, end_date?: string, timezone?: string}  $filters
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
     * @param  array{period?: string, start_date?: string, end_date?: string, timezone?: string}  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveDateRange(array $filters): array
    {
        $timezone = $filters['timezone'] ?? 'UTC';

        if (isset($filters['start_date'], $filters['end_date'])) {
            return [
                CarbonImmutable::parse($filters['start_date'], $timezone)->startOfDay()->utc(),
                CarbonImmutable::parse($filters['end_date'], $timezone)->endOfDay()->utc(),
            ];
        }

        $period = $filters['period'] ?? '30d';

        $startDate = match ($period) {
            '7d' => CarbonImmutable::now($timezone)->subDays(7)->startOfDay()->utc(),
            '90d' => CarbonImmutable::now($timezone)->subDays(90)->startOfDay()->utc(),
            '12m' => CarbonImmutable::now($timezone)->subMonths(12)->startOfDay()->utc(),
            default => CarbonImmutable::now($timezone)->subDays(30)->startOfDay()->utc(),
        };

        return [$startDate, CarbonImmutable::now($timezone)->endOfDay()->utc()];
    }
}
