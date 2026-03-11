<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\WebhookLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogWebhookInteraction
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        $this->log($request, $response, $durationMs);

        return $response;
    }

    protected function log(Request $request, Response $response, int $durationMs): void
    {
        $responseBody = $this->parseResponseBody($response);

        WebhookLog::query()->create([
            'user_id' => $this->resolveUserId($request),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'token' => $request->header('X-Webhook-Token') ?? '',
            'request_body' => $request->all() ?: null,
            'response_status' => $response->getStatusCode(),
            'response_body' => $responseBody,
            'ip_address' => $request->ip(),
            'duration_ms' => $durationMs,
            'created_at' => now(),
        ]);
    }

    protected function resolveUserId(Request $request): ?int
    {
        $phoneNumber = $request->input('phone_number');

        if (! $phoneNumber) {
            return null;
        }

        return User::query()
            ->where('phone_number', $phoneNumber)
            ->value('id');
    }

    protected function parseResponseBody(Response $response): ?array
    {
        $content = $response->getContent();

        if ($content === false || $content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['data']) && is_array($decoded['data']) && count($decoded['data']) > 100) {
            $decoded['data'] = array_slice($decoded['data'], 0, 100);
            $decoded['_truncated'] = true;
        }

        return $decoded;
    }
}
