<?php

namespace App\Http\Resources;

use App\Enums\InvitationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = match (true) {
            ! is_null($this->used_at) => InvitationStatus::Used,
            $this->expires_at->isPast() => InvitationStatus::Expired,
            default => InvitationStatus::Valid,
        };

        return [
            'id' => $this->id,
            'email' => $this->email,
            'token' => $this->token,
            'role' => $this->role->value,
            'status' => $status->value,
            'expires_at' => $this->expires_at->toISOString(),
            'used_at' => $this->used_at?->toISOString(),
            'invited_by' => $this->invited_by,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
