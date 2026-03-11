<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\SendPasswordResetLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Resources\MessageResource;

class ForgotPasswordController extends Controller
{
    public function __invoke(
        ForgotPasswordRequest $request,
        SendPasswordResetLinkAction $action,
    ): MessageResource {
        $action->handle($request->string('email')->value());

        return new MessageResource('If your email is registered, you will receive a password reset link.');
    }
}
