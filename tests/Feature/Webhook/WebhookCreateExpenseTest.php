<?php

namespace Tests\Feature\Webhook;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookCreateExpenseTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookToken = 'test-webhook-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.token' => $this->webhookToken]);
    }

    public function test_can_create_expense_via_webhook(): void
    {
        $user = User::factory()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->postJson('/api/v1/webhooks/expenses', [
            'phone_number' => '5511999999999',
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'description' => 'Almoço via n8n',
            'amount_cents' => 2500,
        ], ['X-Webhook-Token' => $this->webhookToken]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'description', 'amount_cents', 'category', 'payment_method', 'created_at'],
            ]);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2500,
            'description' => 'Almoço via n8n',
        ]);
    }

    public function test_requires_phone_number(): void
    {
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->postJson('/api/v1/webhooks/expenses', [
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2500,
        ], ['X-Webhook-Token' => $this->webhookToken]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_returns_404_for_unknown_phone_number(): void
    {
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->postJson('/api/v1/webhooks/expenses', [
            'phone_number' => '5511000000000',
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2500,
        ], ['X-Webhook-Token' => $this->webhookToken]);

        $response->assertNotFound();
    }

    public function test_returns_403_for_blocked_user(): void
    {
        User::factory()->blocked()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->postJson('/api/v1/webhooks/expenses', [
            'phone_number' => '5511999999999',
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2500,
        ], ['X-Webhook-Token' => $this->webhookToken]);

        $response->assertForbidden();
    }

    public function test_validates_expense_fields(): void
    {
        User::factory()->create(['phone_number' => '5511999999999']);

        $response = $this->postJson('/api/v1/webhooks/expenses', [
            'phone_number' => '5511999999999',
        ], ['X-Webhook-Token' => $this->webhookToken]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'payment_method_id', 'amount_cents']);
    }

    public function test_cannot_create_expense_with_deleted_category(): void
    {
        User::factory()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $category->delete();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->postJson('/api/v1/webhooks/expenses', [
            'phone_number' => '5511999999999',
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2500,
        ], ['X-Webhook-Token' => $this->webhookToken]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_cannot_create_expense_with_deleted_payment_method(): void
    {
        User::factory()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $paymentMethod->delete();

        $response = $this->postJson('/api/v1/webhooks/expenses', [
            'phone_number' => '5511999999999',
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2500,
        ], ['X-Webhook-Token' => $this->webhookToken]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method_id']);
    }
}
