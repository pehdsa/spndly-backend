<?php

namespace App\Actions\Webhooks;

use App\Models\User;

class ResolveUserByPhoneNumberAction
{
    public function handle(string $phoneNumber): User
    {
        $user = User::query()
            ->where('phone_number', $phoneNumber)
            ->first();

        if (! $user) {
            abort(404, 'User not found for the given phone number.');
        }

        if (! $user->isActive()) {
            abort(403, 'User account is blocked.');
        }

        return $user;
    }
}
