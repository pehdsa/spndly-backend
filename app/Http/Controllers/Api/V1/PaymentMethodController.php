<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PaymentMethods\CreatePaymentMethodAction;
use App\Actions\PaymentMethods\DeletePaymentMethodAction;
use App\Actions\PaymentMethods\ListPaymentMethodsAction;
use App\Actions\PaymentMethods\UpdatePaymentMethodAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PaymentMethodController extends Controller
{
    public function index(ListPaymentMethodsAction $action): AnonymousResourceCollection
    {
        return PaymentMethodResource::collection($action->handle());
    }

    public function show(PaymentMethod $paymentMethod): PaymentMethodResource
    {
        return new PaymentMethodResource($paymentMethod);
    }

    public function store(StorePaymentMethodRequest $request, CreatePaymentMethodAction $action): JsonResponse
    {
        Gate::authorize('admin');

        $paymentMethod = $action->handle($request->validated());

        return (new PaymentMethodResource($paymentMethod))->response()->setStatusCode(201);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod, UpdatePaymentMethodAction $action): PaymentMethodResource
    {
        Gate::authorize('admin');

        return new PaymentMethodResource($action->handle($paymentMethod, $request->validated()));
    }

    public function destroy(PaymentMethod $paymentMethod, DeletePaymentMethodAction $action): MessageResource
    {
        Gate::authorize('admin');

        $action->handle($paymentMethod);

        return new MessageResource('Payment method deleted successfully.');
    }
}
