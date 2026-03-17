<?php

namespace App\Jobs;

use App\Models\Invitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendInvitationToN8nWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $invitationId) {}

    public function handle(): void
    {
        $webhookUrl = config('services.n8n.invitation_webhook_url');

        if (empty($webhookUrl)) {
            $this->fail(new \RuntimeException('N8N_INVITATION_WEBHOOK_URL is not configured.'));

            return;
        }

        $invitation = Invitation::query()->with('inviter')->find($this->invitationId);

        if (! $invitation) {
            Log::warning('SendInvitationToN8nWebhookJob: invitation not found, skipping.', [
                'invitation_id' => $this->invitationId,
            ]);

            return;
        }

        if (! $invitation->isValid()) {
            Log::warning('SendInvitationToN8nWebhookJob: invitation is no longer valid, skipping.', [
                'invitation_id' => $this->invitationId,
                'used_at' => $invitation->used_at?->toISOString(),
                'expires_at' => $invitation->expires_at->toISOString(),
            ]);

            return;
        }

        $payload = [
            'invitation_id' => $invitation->id,
            'idempotency_key' => "invitation:{$invitation->id}",
            'phone_number' => $invitation->phone_number,
            'register_url' => config('app.frontend_url').'/register?token='.$invitation->token,
            'token' => $invitation->token,
            'role' => $invitation->role->value,
            'expires_at' => $invitation->expires_at->toISOString(),
            'invited_by' => $invitation->inviter?->name,
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
                Log::info('SendInvitationToN8nWebhookJob: webhook sent successfully.', [
                    'invitation_id' => $invitation->id,
                    'phone_number' => $invitation->phone_number,
                    'status' => $response->status(),
                    'attempt' => $this->attempts(),
                ]);

                return;
            }

            if ($response->clientError()) {
                Log::error('SendInvitationToN8nWebhookJob: client error, failing permanently.', [
                    'invitation_id' => $invitation->id,
                    'phone_number' => $invitation->phone_number,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                    'attempt' => $this->attempts(),
                ]);

                $this->fail(new RequestException($response));

                return;
            }

            // 5xx — throw to trigger retry
            $response->throw();
        } catch (ConnectionException $e) {
            Log::warning('SendInvitationToN8nWebhookJob: connection error, will retry.', [
                'invitation_id' => $invitation->id,
                'phone_number' => $invitation->phone_number,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendInvitationToN8nWebhookJob: job failed permanently.', [
            'invitation_id' => $this->invitationId,
            'error' => $exception->getMessage(),
            'attempt' => $this->attempts(),
        ]);
    }
}
