<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()->token();
        $token->revoke();
        $token->refreshToken?->revoke();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
