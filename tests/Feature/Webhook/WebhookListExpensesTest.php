<?php

namespace Tests\Feature\Webhook;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookListExpensesTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookToken = 'test-webhook-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.token' => $this->webhookToken]);
    }

    public function test_can_list_expenses_via_webhook(): void
    {
        $user = User::factory()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response = $this->getJson('/api/v1/webhooks/expenses?phone_number=5511999999999', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'description', 'amount_cents', 'category', 'payment_method', 'created_at'],
                ],
            ]);
    }

    public function test_returns_only_user_expenses(): void
    {
        $user1 = User::factory()->create(['phone_number' => '5511999999999']);
        $user2 = User::factory()->create(['phone_number' => '5511888888888']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->count(2)->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
        Expense::factory()->count(3)->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response = $this->getJson('/api/v1/webhooks/expenses?phone_number=5511999999999', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_requires_phone_number(): void
    {
        $response = $this->getJson('/api/v1/webhooks/expenses', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_returns_404_for_unknown_phone_number(): void
    {
        $response = $this->getJson('/api/v1/webhooks/expenses?phone_number=5511000000000', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertNotFound();
    }

    public function test_returns_403_for_blocked_user(): void
    {
        User::factory()->blocked()->create(['phone_number' => '5511999999999']);

        $response = $this->getJson('/api/v1/webhooks/expenses?phone_number=5511999999999', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertForbidden();
    }

    public function test_filters_by_period(): void
    {
        $user = User::factory()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'created_at' => now()->subDays(3),
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/v1/webhooks/expenses?phone_number=5511999999999&period=7d', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filters_by_date_range_with_timezone(): void
    {
        $user = User::factory()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        // 14/03 at 23:00 São Paulo = 15/03 at 02:00 UTC
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'created_at' => '2026-03-15 02:00:00',
        ]);

        // 14/03 at 12:00 São Paulo = 14/03 at 15:00 UTC
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'created_at' => '2026-03-14 15:00:00',
        ]);

        // 13/03 at 22:00 São Paulo = 14/03 at 01:00 UTC (should NOT be included)
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'created_at' => '2026-03-14 01:00:00',
        ]);

        $response = $this->getJson(
            '/api/v1/webhooks/expenses?phone_number=5511999999999&start_date=2026-03-14&end_date=2026-03-14&timezone=America/Sao_Paulo',
            ['X-Webhook-Token' => $this->webhookToken],
        );

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_by_date_range_defaults_to_utc_without_timezone(): void
    {
        $user = User::factory()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        // 14/03 at 23:00 São Paulo = 15/03 at 02:00 UTC (outside UTC day 14)
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'created_at' => '2026-03-15 02:00:00',
        ]);

        // 14/03 at 15:00 UTC (inside UTC day 14)
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'created_at' => '2026-03-14 15:00:00',
        ]);

        $response = $this->getJson(
            '/api/v1/webhooks/expenses?phone_number=5511999999999&start_date=2026-03-14&end_date=2026-03-14',
            ['X-Webhook-Token' => $this->webhookToken],
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_returns_not_paginated(): void
    {
        $user = User::factory()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->count(5)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response = $this->getJson('/api/v1/webhooks/expenses?phone_number=5511999999999', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonMissing(['meta', 'links']);
    }
}
