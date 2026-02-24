<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUsersAction
{
    public function handle(?string $search = null): LengthAwarePaginator
    {
        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%{$search}%"]);
            });
        }

        return $query->paginateFromRequest(orderBy: 'name', initialDirection: 'asc');
    }
}
