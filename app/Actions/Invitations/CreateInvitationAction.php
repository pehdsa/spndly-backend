<?php

namespace App\Actions\Invitations;

use App\DTOs\BatchInvitationResultData;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CreateInvitationAction
{
    /**
     * @param  string[]  $emails
     */
    public function handle(User $inviter, array $emails, string $role): BatchInvitationResultData
    {
        $sent = [];
        $failed = [];

        foreach ($emails as $email) {
            $activeUserExists = User::query()
                ->where('email', $email)
                ->exists();

            if ($activeUserExists) {
                $failed[] = ['email' => $email, 'reason' => 'A user with this email address already exists.'];

                continue;
            }

            $validInvitationExists = Invitation::query()
                ->where('email', $email)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($validInvitationExists) {
                $failed[] = ['email' => $email, 'reason' => 'A valid invitation for this email address already exists.'];

                continue;
            }

            $invitation = Invitation::query()->create([
                'email' => $email,
                'role' => $role,
                'token' => Str::random(64),
                'expires_at' => now()->addHours(48),
                'invited_by' => $inviter->id,
            ]);

            Notification::route('mail', $email)
                ->notify(new InvitationNotification($invitation));

            $sent[] = $invitation;
        }

        return new BatchInvitationResultData(sent: $sent, failed: $failed);
    }
}
