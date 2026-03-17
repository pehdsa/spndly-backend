<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ValidateInvitationTokenAction;
use App\Actions\Invitations\CreateInvitationAction;
use App\Actions\Invitations\DeleteInvitationAction;
use App\Actions\Invitations\ListInvitationsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateInvitationRequest;
use App\Http\Resources\BatchInvitationResource;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\MessageResource;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvitationController extends Controller
{
    public function index(Request $request, ListInvitationsAction $action): AnonymousResourceCollection
    {
        $invitations = $action->handle(search: $request->string('search')->value() ?: null);

        return InvitationResource::collection($invitations);
    }

    public function store(
        CreateInvitationRequest $request,
        CreateInvitationAction $action,
    ): JsonResponse {
        $result = $action->handle(
            inviter: $request->user(),
            phoneNumbers: $request->input('phone_numbers'),
            role: $request->string('role')->value(),
        );

        return (new BatchInvitationResource($result))->response()->setStatusCode(201);
    }

    public function destroy(Invitation $invitation, DeleteInvitationAction $action): MessageResource
    {
        $action->handle($invitation);

        return new MessageResource('Invitation deleted successfully.');
    }

    public function validateToken(Request $request, ValidateInvitationTokenAction $action): InvitationResource
    {
        $invitation = $action->handle($request->string('token')->value());

        return new InvitationResource($invitation);
    }
}
