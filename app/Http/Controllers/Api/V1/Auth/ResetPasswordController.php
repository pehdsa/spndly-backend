<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\MessageResource;

class ResetPasswordController extends Controller
{
    public function __invoke(
        ResetPasswordRequest $request,
        ResetPasswordAction $action,
    ): MessageResource {
        $action->handle(
            token: $request->string('token')->value(),
            email: $request->string('email')->value(),
            password: $request->string('password')->value(),
        );

        return new MessageResource('Your password has been reset successfully.');
    }
}
