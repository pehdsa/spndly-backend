<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    public function handle(string $token, string $email, string $password): void
    {
        $status = Password::broker()->reset(
            ['token' => $token, 'email' => $email, 'password' => $password],
            function (User $user, string $password): void {
                if (! $user->isActive()) {
                    throw ValidationException::withMessages([
                        'email' => [__('passwords.user')],
                    ]);
                }

                $user->update(['password' => $password]);

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [__($status)],
            ]);
        }
    }
}
