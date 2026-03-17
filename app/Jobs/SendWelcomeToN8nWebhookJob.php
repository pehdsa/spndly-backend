<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWelcomeToN8nWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $userId) {}

    public function handle(): void
    {
        $webhookUrl = config('services.n8n.welcome_webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('SendWelcomeToN8nWebhookJob: webhook URL not configured, skipping.', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('SendWelcomeToN8nWebhookJob: user not found, skipping.', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $webhookHost = parse_url($webhookUrl, PHP_URL_HOST);

        $payload = [
            'user_id' => $user->id,
            'idempotency_key' => "welcome:{$user->id}",
            'name' => $user->name,
            'phone_number' => $user->phone_number,
            'email' => $user->email,
            'role' => $user->role->value,
            'app_name' => config('app.name'),
            'attempt' => $this->attempts(),
        ];

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(10)
                ->connectTimeout(5)
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('SendWelcomeToN8nWebhookJob: webhook sent successfully.', [
                    'user_id' => $user->id,
                    'webhook_host' => $webhookHost,
                    'status' => $response->status(),
                    'attempt' => $this->attempts(),
                ]);

                return;
            }

            if ($response->status() === 429) {
                Log::warning('SendWelcomeToN8nWebhookJob: rate limited, will retry.', [
                    'user_id' => $user->id,
                    'webhook_host' => $webhookHost,
                    'status' => $response->status(),
                    'attempt' => $this->attempts(),
                ]);

                $response->throw();
            }

            if ($response->clientError()) {
                Log::error('SendWelcomeToN8nWebhookJob: client error, failing permanently.', [
                    'user_id' => $user->id,
                    'webhook_host' => $webhookHost,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                    'attempt' => $this->attempts(),
                ]);

                $this->fail(new \RuntimeException(
                    "Welcome webhook client error: {$response->status()}"
                ));

                return;
            }

            // 5xx — throw to trigger retry
            Log::warning('SendWelcomeToN8nWebhookJob: server error, will retry.', [
                'user_id' => $user->id,
                'webhook_host' => $webhookHost,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
                'attempt' => $this->attempts(),
            ]);

            $response->throw();
        } catch (ConnectionException $e) {
            Log::warning('SendWelcomeToN8nWebhookJob: connection error, will retry.', [
                'user_id' => $user->id,
                'webhook_host' => $webhookHost,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendWelcomeToN8nWebhookJob: job failed permanently.', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempt' => $this->attempts(),
        ]);
    }
}
