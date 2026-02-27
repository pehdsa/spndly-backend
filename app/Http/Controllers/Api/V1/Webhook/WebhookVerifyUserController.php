<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Actions\Webhooks\ResolveUserByPhoneNumberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\WebhookPhoneNumberRequest;
use App\Http\Resources\UserResource;

class WebhookVerifyUserController extends Controller
{
    public function __invoke(
        WebhookPhoneNumberRequest $request,
        ResolveUserByPhoneNumberAction $resolveUser,
    ): UserResource {
        $user = $resolveUser->handle($request->validated('phone_number'));

        return new UserResource($user);
    }
}
