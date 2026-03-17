<?php

namespace App\Actions\Invitations;

use App\Models\Invitation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListInvitationsAction
{
    public function handle(?string $search = null): LengthAwarePaginator
    {
        $query = Invitation::query();

        if ($search) {
            $normalizedSearch = preg_replace('/\D/', '', $search);

            if ($normalizedSearch !== '') {
                $query->where('phone_number', 'LIKE', "%{$normalizedSearch}%");
            }
        }

        return $query->paginateFromRequest();
    }
}
