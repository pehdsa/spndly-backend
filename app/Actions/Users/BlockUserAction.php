<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BlockUserAction
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->tokens()->update(['revoked' => true]);
            $user->update(['status' => UserStatus::Blocked]);
        });
    }
}
