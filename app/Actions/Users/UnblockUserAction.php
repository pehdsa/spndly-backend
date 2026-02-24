<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;

class UnblockUserAction
{
    public function handle(User $user): void
    {
        $user->update(['status' => UserStatus::Active]);
    }
}
