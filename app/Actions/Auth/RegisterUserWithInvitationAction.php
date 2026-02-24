<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\Invitation;
use App\Models\User;

class RegisterUserWithInvitationAction
{
    public function handle(Invitation $invitation, string $name, string $password): User
    {
        $existingSoftDeleted = User::query()
            ->withTrashed()
            ->where('email', $invitation->email)
            ->whereNotNull('deleted_at')
            ->first();

        if ($existingSoftDeleted) {
            $existingSoftDeleted->restore();
            $existingSoftDeleted->update([
                'name' => $name,
                'password' => $password,
                'role' => $invitation->role,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);

            $invitation->update(['used_at' => now()]);

            return $existingSoftDeleted->fresh();
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $invitation->email,
            'password' => $password,
            'role' => $invitation->role,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $invitation->update(['used_at' => now()]);

        return $user;
    }
}
