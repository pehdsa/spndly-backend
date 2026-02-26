<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class PaymentMethodPolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
