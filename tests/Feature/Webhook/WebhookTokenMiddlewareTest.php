<?php

namespace Tests\Feature\Webhook;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTokenMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookToken = 'test-webhook-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.token' => $this->webhookToken]);
    }

    public function test_request_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/webhooks/categories');

        $response->assertUnauthorized();
    }

    public function test_request_with_invalid_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/webhooks/categories', [
            'X-Webhook-Token' => 'wrong-token',
        ]);

        $response->assertUnauthorized();
    }

    public function test_request_with_valid_token_passes(): void
    {
        $response = $this->getJson('/api/v1/webhooks/categories', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk();
    }

    public function test_request_returns_401_when_webhook_token_not_configured(): void
    {
        config(['services.webhook.token' => null]);

        $response = $this->getJson('/api/v1/webhooks/categories', [
            'X-Webhook-Token' => 'any-token',
        ]);

        $response->assertUnauthorized();
    }
}
