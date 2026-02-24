<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (is_null($user->viewed_at) || $user->viewed_at->diffInMinutes(now()) > 5)) {
            $user->update(['viewed_at' => now()]);
        }

        return $next($request);
    }
}
