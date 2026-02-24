<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Http\Resources\MessageResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status !== UserStatus::Active) {
            return (new MessageResource('Your account has been blocked.'))->response()->setStatusCode(403);
        }

        return $next($request);
    }
}
