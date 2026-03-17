<?php

namespace App\Actions\Invitations;

use App\DTOs\BatchInvitationResultData;
use App\Jobs\SendInvitationToN8nWebhookJob;
use App\Models\Invitation;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Str;

class CreateInvitationAction
{
    /**
     * @param  string[]  $phoneNumbers
     */
    public function handle(User $inviter, array $phoneNumbers, string $role): BatchInvitationResultData
    {
        $sent = [];
        $failed = [];

        foreach ($phoneNumbers as $phoneNumber) {
            $activeUserExists = User::query()
                ->whereIn('phone_number', PhoneNumber::variants($phoneNumber))
                ->exists();

            if ($activeUserExists) {
                $failed[] = ['phone_number' => $phoneNumber, 'reason' => 'A user with this phone number already exists.'];

                continue;
            }

            $validInvitationExists = Invitation::query()
                ->whereIn('phone_number', PhoneNumber::variants($phoneNumber))
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($validInvitationExists) {
                $failed[] = ['phone_number' => $phoneNumber, 'reason' => 'A valid invitation for this phone number already exists.'];

                continue;
            }

            $invitation = Invitation::query()->create([
                'phone_number' => $phoneNumber,
                'role' => $role,
                'token' => Str::random(64),
                'expires_at' => now()->addHours(48),
                'invited_by' => $inviter->id,
            ]);

            SendInvitationToN8nWebhookJob::dispatch($invitation->id);

            $sent[] = $invitation;
        }

        return new BatchInvitationResultData(sent: $sent, failed: $failed);
    }
}
