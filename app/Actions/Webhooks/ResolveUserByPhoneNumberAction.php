<?php

namespace App\Actions\Webhooks;

use App\Models\User;
use App\Support\PhoneNumber;

class ResolveUserByPhoneNumberAction
{
    public function handle(string $phoneNumber): User
    {
        $user = User::query()
            ->whereIn('phone_number', PhoneNumber::variants($phoneNumber))
            ->orderByRaw('phone_number = ? DESC', [$phoneNumber])
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
