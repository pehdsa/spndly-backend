<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendInvitationToN8nWebhookJob;
use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendInvitationToN8nWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_correct_payload_to_webhook(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $invitation = Invitation::factory()->create([
            'phone_number' => '5567999999999',
        ]);

        config(['services.n8n.invitation_webhook_url' => 'https://n8n.example.com/webhook/invitation']);

        $job = new SendInvitationToN8nWebhookJob($invitation->id);
        $job->handle();

        Http::assertSent(function ($request) use ($invitation) {
            return $request->url() === 'https://n8n.example.com/webhook/invitation'
                && $request['invitation_id'] === $invitation->id
                && $request['phone_number'] === '5567999999999'
                && $request['token'] === $invitation->token
                && $request['role'] === $invitation->role->value
                && isset($request['register_url'])
                && isset($request['expires_at'])
                && isset($request['app_name'])
                && $request['idempotency_key'] === "invitation:{$invitation->id}";
        });
    }

    public function test_job_includes_attempt_in_payload(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $invitation = Invitation::factory()->create();

        config(['services.n8n.invitation_webhook_url' => 'https://n8n.example.com/webhook/invitation']);

        $job = new SendInvitationToN8nWebhookJob($invitation->id);
        $job->handle();

        Http::assertSent(function ($request) {
            return isset($request['attempt']);
        });
    }

    public function test_job_skips_with_warning_when_invitation_not_found(): void
    {
        Http::fake();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'invitation not found'));

        config(['services.n8n.invitation_webhook_url' => 'https://n8n.example.com/webhook/invitation']);

        $job = new SendInvitationToN8nWebhookJob(99999);
        $job->handle();

        Http::assertNothingSent();
    }

    public function test_job_skips_with_warning_when_invitation_already_used(): void
    {
        Http::fake();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'no longer valid'));

        $invitation = Invitation::factory()->used()->create();

        config(['services.n8n.invitation_webhook_url' => 'https://n8n.example.com/webhook/invitation']);

        $job = new SendInvitationToN8nWebhookJob($invitation->id);
        $job->handle();

        Http::assertNothingSent();
    }

    public function test_job_skips_with_warning_when_invitation_expired(): void
    {
        Http::fake();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'no longer valid'));

        $invitation = Invitation::factory()->expired()->create();

        config(['services.n8n.invitation_webhook_url' => 'https://n8n.example.com/webhook/invitation']);

        $job = new SendInvitationToN8nWebhookJob($invitation->id);
        $job->handle();

        Http::assertNothingSent();
    }

    public function test_job_fails_when_webhook_url_not_configured(): void
    {
        config(['services.n8n.invitation_webhook_url' => null]);

        $invitation = Invitation::factory()->create();

        $job = $this->getMockBuilder(SendInvitationToN8nWebhookJob::class)
            ->setConstructorArgs([$invitation->id])
            ->onlyMethods(['fail'])
            ->getMock();

        $job->expects($this->once())->method('fail');

        $job->handle();
    }

    public function test_job_throws_on_server_error_for_retry(): void
    {
        Http::fake(['*' => Http::response('Server Error', 500)]);

        $invitation = Invitation::factory()->create();

        config(['services.n8n.invitation_webhook_url' => 'https://n8n.example.com/webhook/invitation']);

        $job = new SendInvitationToN8nWebhookJob($invitation->id);

        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        $job->handle();
    }

    public function test_job_does_not_retry_on_client_error(): void
    {
        Http::fake(['*' => Http::response('Bad Request', 400)]);

        $invitation = Invitation::factory()->create();

        config(['services.n8n.invitation_webhook_url' => 'https://n8n.example.com/webhook/invitation']);

        $job = $this->getMockBuilder(SendInvitationToN8nWebhookJob::class)
            ->setConstructorArgs([$invitation->id])
            ->onlyMethods(['fail'])
            ->getMock();

        $job->expects($this->once())->method('fail');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'client error'));

        Log::shouldReceive('info')->never();

        $job->handle();
    }

    public function test_job_has_correct_retry_configuration(): void
    {
        $job = new SendInvitationToN8nWebhookJob(1);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([10, 60, 300], $job->backoff);
    }
}
