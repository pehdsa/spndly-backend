<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Actions\Webhooks\ListWebhookPaymentMethodsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebhookPaymentMethodController extends Controller
{
    public function __invoke(ListWebhookPaymentMethodsAction $action): AnonymousResourceCollection
    {
        return PaymentMethodResource::collection($action->handle());
    }
}
