<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Actions\Webhooks\ListWebhookCategoriesAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebhookCategoryController extends Controller
{
    public function __invoke(ListWebhookCategoriesAction $action): AnonymousResourceCollection
    {
        return CategoryResource::collection($action->handle());
    }
}
