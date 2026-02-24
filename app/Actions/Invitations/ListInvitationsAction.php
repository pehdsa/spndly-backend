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
            $query->whereRaw('LOWER(email) LIKE LOWER(?)', ["%{$search}%"]);
        }

        return $query->paginateFromRequest();
    }
}
