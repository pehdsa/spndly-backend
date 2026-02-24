<?php

namespace App\Actions\Invitations;

use App\Models\Invitation;

class DeleteInvitationAction
{
    public function handle(Invitation $invitation): void
    {
        $invitation->delete();
    }
}
