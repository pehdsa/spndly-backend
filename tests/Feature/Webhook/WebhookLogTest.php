<?php

namespace Tests\Feature\Webhook;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookLogTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookToken = 'test-webhook-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.token' => $this->webhookToken]);
    }

    public function test_logs_successful_request(): void
    {
        Category::factory()->count(2)->create();

        $this->getJson('/api/v1/webhooks/categories', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $this->assertDatabaseCount('webhook_logs', 1);

        $log = WebhookLog::first();
        $this->assertEquals('GET', $log->method);
        $this->assertStringContains('/api/v1/webhooks/categories', $log->url);
        $this->assertEquals($this->webhookToken, $log->token);
        $this->assertEquals(200, $log->response_status);
        $this->assertNull($log->user_id);
        $this->assertIsArray($log->response_body);
        $this->assertNotNull($log->created_at);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
    }

    public function test_logs_request_with_user(): void
    {
        $user = User::factory()->create(['phone_number' => '5511999999999']);

        $this->getJson('/api/v1/webhooks/verify-user?phone_number=5511999999999', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $this->assertDatabaseCount('webhook_logs', 1);

        $log = WebhookLog::first();
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals(200, $log->response_status);
    }

    public function test_logs_failed_request_with_401(): void
    {
        $this->getJson('/api/v1/webhooks/categories', [
            'X-Webhook-Token' => 'wrong-token',
        ]);

        $this->assertDatabaseCount('webhook_logs', 1);

        $log = WebhookLog::first();
        $this->assertEquals(401, $log->response_status);
    }

    public function test_logs_failed_request_with_422(): void
    {
        $this->getJson('/api/v1/webhooks/verify-user', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $this->assertDatabaseCount('webhook_logs', 1);

        $log = WebhookLog::first();
        $this->assertEquals(422, $log->response_status);
    }

    public function test_logs_post_request_body(): void
    {
        $user = User::factory()->create(['phone_number' => '5511999999999']);
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $this->postJson('/api/v1/webhooks/expenses', [
            'phone_number' => '5511999999999',
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1500,
            'description' => 'Test expense',
        ], ['X-Webhook-Token' => $this->webhookToken]);

        $log = WebhookLog::first();
        $this->assertEquals('POST', $log->method);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals(201, $log->response_status);
        $this->assertIsArray($log->request_body);
        $this->assertEquals('5511999999999', $log->request_body['phone_number']);
        $this->assertEquals(1500, $log->request_body['amount_cents']);
    }

    public function test_logs_404_for_unknown_user(): void
    {
        $this->getJson('/api/v1/webhooks/verify-user?phone_number=5511000000000', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $log = WebhookLog::first();
        $this->assertEquals(404, $log->response_status);
        $this->assertNull($log->user_id);
    }

    /**
     * Helper to check string contains substring.
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
