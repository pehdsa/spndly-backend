<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterUserWithInvitationAction;
use App\Actions\Auth\ValidateInvitationTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function __invoke(
        RegisterRequest $request,
        ValidateInvitationTokenAction $validateToken,
        RegisterUserWithInvitationAction $registerUser,
    ): JsonResponse {
        $user = DB::transaction(function () use ($request, $validateToken, $registerUser) {
            $invitation = $validateToken->handle($request->string('token')->value());

            return $registerUser->handle(
                invitation: $invitation,
                name: $request->string('name')->value(),
                password: $request->string('password')->value(),
                phoneNumber: $request->string('phone_number')->value(),
            );
        });

        $tokenRequest = Request::create('/oauth/token', 'POST', [
            'grant_type' => 'password',
            'client_id' => $request->input('client_id'),
            'client_secret' => $request->input('client_secret'),
            'username' => $user->email,
            'password' => $request->input('password'),
            'scope' => '*',
        ]);

        $tokenResponse = app()->handle($tokenRequest);
        $tokenData = json_decode($tokenResponse->getContent(), true);

        if ($tokenResponse->getStatusCode() !== 200) {
            abort(500, 'Failed to issue authentication tokens.');
        }

        return response()->json([
            'user' => new UserResource($user),
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'expires_in' => $tokenData['expires_in'],
        ], 201);
    }
}
