<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction
{
    public function handle(string $email): string
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user || ! $user->isActive()) {
            return Password::RESET_LINK_SENT;
        }

        return Password::broker()->sendResetLink(['email' => $email]);
    }
}
