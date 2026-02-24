<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUserAction
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->tokens()->update(['revoked' => true]);
            $user->delete();
        });
    }
}
