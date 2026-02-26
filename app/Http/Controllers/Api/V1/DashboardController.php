<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\GetDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest;
use App\Http\Resources\DashboardResource;

class DashboardController extends Controller
{
    public function __invoke(DashboardRequest $request, GetDashboardAction $action): DashboardResource
    {
        return new DashboardResource($action->handle($request->user(), $request->validated()));
    }
}
