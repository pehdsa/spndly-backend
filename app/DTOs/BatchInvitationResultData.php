<?php

namespace App\DTOs;

use App\Models\Invitation;

class BatchInvitationResultData
{
    /**
     * @param  Invitation[]  $sent
     * @param  array<int, array{email: string, reason: string}>  $failed
     */
    public function __construct(
        public readonly array $sent,
        public readonly array $failed,
    ) {}
}
