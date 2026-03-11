<?php

namespace Tests\Feature\Webhook;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookListCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookToken = 'test-webhook-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.token' => $this->webhookToken]);
    }

    public function test_can_list_categories_via_webhook(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/webhooks/categories', [
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
        $response = $this->getJson('/api/v1/webhooks/categories');

        $response->assertUnauthorized();
    }

    public function test_does_not_return_deleted_categories(): void
    {
        Category::factory()->count(2)->create();
        $deleted = Category::factory()->create();
        $deleted->delete();

        $response = $this->getJson('/api/v1/webhooks/categories', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_not_paginated(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/webhooks/categories', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonMissing(['meta', 'links']);
    }
}
