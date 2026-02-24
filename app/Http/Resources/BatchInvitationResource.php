<?php

namespace App\Http\Resources;

use App\DTOs\BatchInvitationResultData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchInvitationResource extends JsonResource
{
    public function __construct(public readonly BatchInvitationResultData $result) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sent' => InvitationResource::collection($this->result->sent),
            'failed' => $this->result->failed,
        ];
    }
}
