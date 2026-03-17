<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendWelcomeToN8nWebhookJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendWelcomeToN8nWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_correct_payload_to_webhook(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $user = User::factory()->create([
            'phone_number' => '5567999999999',
        ]);

        config(['services.n8n.welcome_webhook_url' => 'https://n8n.example.com/webhook/welcome']);

        $job = new SendWelcomeToN8nWebhookJob($user->id);
        $job->handle();

        Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'https://n8n.example.com/webhook/welcome'
                && $request['user_id'] === $user->id
                && $request['idempotency_key'] === "welcome:{$user->id}"
                && $request['name'] === $user->name
                && $request['phone_number'] === '5567999999999'
                && $request['email'] === $user->email
                && $request['role'] === $user->role->value
                && isset($request['app_name'])
                && isset($request['attempt']);
        });
    }

    public function test_job_skips_when_webhook_url_not_configured(): void
    {
        Http::fake();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'webhook URL not configured'));

        config(['services.n8n.welcome_webhook_url' => null]);

        $user = User::factory()->create();

        $job = new SendWelcomeToN8nWebhookJob($user->id);
        $job->handle();

        Http::assertNothingSent();
    }

    public function test_job_skips_with_warning_when_user_not_found(): void
    {
        Http::fake();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'user not found'));

        config(['services.n8n.welcome_webhook_url' => 'https://n8n.example.com/webhook/welcome']);

        $job = new SendWelcomeToN8nWebhookJob(99999);
        $job->handle();

        Http::assertNothingSent();
    }

    public function test_job_throws_on_server_error_for_retry(): void
    {
        Http::fake(['*' => Http::response('Server Error', 500)]);

        $user = User::factory()->create();

        config(['services.n8n.welcome_webhook_url' => 'https://n8n.example.com/webhook/welcome']);

        $job = new SendWelcomeToN8nWebhookJob($user->id);

        $this->expectException(RequestException::class);

        $job->handle();
    }

    public function test_job_throws_on_rate_limit_for_retry(): void
    {
        Http::fake(['*' => Http::response('Too Many Requests', 429)]);

        $user = User::factory()->create();

        config(['services.n8n.welcome_webhook_url' => 'https://n8n.example.com/webhook/welcome']);

        $job = new SendWelcomeToN8nWebhookJob($user->id);

        $this->expectException(RequestException::class);

        $job->handle();
    }

    public function test_job_fails_permanently_on_client_error(): void
    {
        Http::fake(['*' => Http::response('Bad Request', 400)]);

        $user = User::factory()->create();

        config(['services.n8n.welcome_webhook_url' => 'https://n8n.example.com/webhook/welcome']);

        $job = $this->getMockBuilder(SendWelcomeToN8nWebhookJob::class)
            ->setConstructorArgs([$user->id])
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
        $job = new SendWelcomeToN8nWebhookJob(1);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([10, 60, 300], $job->backoff);
    }
}
