<?php

namespace App\Actions\Auth;

use App\Models\Invitation;
use Illuminate\Validation\ValidationException;

class ValidateInvitationTokenAction
{
    public function handle(string $token): Invitation
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->first();

        if (! $invitation) {
            throw ValidationException::withMessages([
                'token' => ['This invitation token is invalid.'],
            ]);
        }

        if (! is_null($invitation->used_at)) {
            throw ValidationException::withMessages([
                'token' => ['This invitation has already been used.'],
            ]);
        }

        if ($invitation->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation has expired.'],
            ]);
        }

        return $invitation;
    }
}
