<?php

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\User;

class UpdateUserRoleAction
{
    public function handle(User $user, UserRole $role): void
    {
        $user->update(['role' => $role]);
    }
}
