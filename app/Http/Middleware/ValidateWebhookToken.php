<?php

namespace App\Http\Middleware;

use App\Http\Resources\MessageResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebhookToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Webhook-Token') ?? '';
        $expectedToken = config('services.webhook.token') ?? '';

        if ($expectedToken === '' || ! hash_equals($expectedToken, $token)) {
            return (new MessageResource('Invalid or missing webhook token.'))->response()->setStatusCode(401);
        }

        return $next($request);
    }
}
