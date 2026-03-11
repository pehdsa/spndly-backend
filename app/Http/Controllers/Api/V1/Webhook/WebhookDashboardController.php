<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Actions\Dashboard\GetDashboardAction;
use App\Actions\Webhooks\ResolveUserByPhoneNumberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\WebhookDashboardRequest;
use App\Http\Resources\DashboardResource;

class WebhookDashboardController extends Controller
{
    public function __invoke(
        WebhookDashboardRequest $request,
        ResolveUserByPhoneNumberAction $resolveUser,
        GetDashboardAction $action,
    ): DashboardResource {
        $user = $resolveUser->handle($request->validated('phone_number'));

        $filters = $request->safe()->except(['phone_number']);

        return new DashboardResource($action->handle($user, $filters));
    }
}
