<?php

namespace Tests\Feature\Webhook;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookListPaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookToken = 'test-webhook-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.token' => $this->webhookToken]);
    }

    public function test_can_list_payment_methods_via_webhook(): void
    {
        PaymentMethod::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/webhooks/payment-methods', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'created_at'],
                ],
            ]);
    }

    public function test_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/v1/webhooks/payment-methods');

        $response->assertUnauthorized();
    }

    public function test_does_not_return_deleted_payment_methods(): void
    {
        PaymentMethod::factory()->count(2)->create();
        $deleted = PaymentMethod::factory()->create();
        $deleted->delete();

        $response = $this->getJson('/api/v1/webhooks/payment-methods', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_not_paginated(): void
    {
        PaymentMethod::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/webhooks/payment-methods', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonMissing(['meta', 'links']);
    }
}
