<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\Invitation;
use App\Models\User;

class RegisterUserWithInvitationAction
{
    public function handle(Invitation $invitation, string $name, string $email, string $password): User
    {
        $existingSoftDeleted = User::query()
            ->withTrashed()
            ->where('email', $email)
            ->whereNotNull('deleted_at')
            ->first();

        if ($existingSoftDeleted) {
            $existingSoftDeleted->restore();
            $existingSoftDeleted->update([
                'name' => $name,
                'phone_number' => $invitation->phone_number,
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
            'email' => $email,
            'phone_number' => $invitation->phone_number,
            'password' => $password,
            'role' => $invitation->role,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $invitation->update(['used_at' => now()]);

        return $user;
    }
}
