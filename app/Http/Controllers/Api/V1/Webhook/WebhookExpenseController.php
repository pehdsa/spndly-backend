<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Actions\Expenses\CreateExpenseAction;
use App\Actions\Webhooks\ListWebhookExpensesAction;
use App\Actions\Webhooks\ResolveUserByPhoneNumberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\WebhookListExpensesRequest;
use App\Http\Requests\Webhook\WebhookStoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebhookExpenseController extends Controller
{
    public function index(
        WebhookListExpensesRequest $request,
        ResolveUserByPhoneNumberAction $resolveUser,
        ListWebhookExpensesAction $action,
    ): AnonymousResourceCollection {
        $user = $resolveUser->handle($request->validated('phone_number'));

        $filters = $request->safe()->except(['phone_number']);

        return ExpenseResource::collection($action->handle($user, $filters));
    }

    public function store(
        WebhookStoreExpenseRequest $request,
        ResolveUserByPhoneNumberAction $resolveUser,
        CreateExpenseAction $action,
    ): JsonResponse {
        $user = $resolveUser->handle($request->validated('phone_number'));

        $expense = $action->handle($user, $request->safe()->except(['phone_number']));

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }
}
