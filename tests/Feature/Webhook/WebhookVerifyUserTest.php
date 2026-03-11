<?php

namespace Tests\Feature\Webhook;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookVerifyUserTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookToken = 'test-webhook-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.token' => $this->webhookToken]);
    }

    public function test_can_verify_active_user(): void
    {
        $user = User::factory()->create(['phone_number' => '5511999999999']);

        $response = $this->getJson('/api/v1/webhooks/verify-user?phone_number=5511999999999', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'phone_number', 'role', 'status', 'is_blocked'],
            ])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.phone_number', '5511999999999')
            ->assertJsonPath('data.is_blocked', false);
    }

    public function test_returns_404_for_unknown_phone_number(): void
    {
        $response = $this->getJson('/api/v1/webhooks/verify-user?phone_number=5511000000000', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertNotFound();
    }

    public function test_returns_403_for_blocked_user(): void
    {
        User::factory()->blocked()->create(['phone_number' => '5511999999999']);

        $response = $this->getJson('/api/v1/webhooks/verify-user?phone_number=5511999999999', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertForbidden();
    }

    public function test_returns_is_blocked_true_for_blocked_user(): void
    {
        User::factory()->isBlocked()->create(['phone_number' => '5511999999999']);

        $response = $this->getJson('/api/v1/webhooks/verify-user?phone_number=5511999999999', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_blocked', true);
    }

    public function test_requires_phone_number(): void
    {
        $response = $this->getJson('/api/v1/webhooks/verify-user', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_rejects_non_numeric_phone_number(): void
    {
        $response = $this->getJson('/api/v1/webhooks/verify-user?phone_number=abc123', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_finds_user_when_webhook_sends_number_without_ninth_digit(): void
    {
        $user = User::factory()->create(['phone_number' => '5511987654321']);

        $response = $this->getJson('/api/v1/webhooks/verify-user?phone_number=551187654321', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_finds_user_when_webhook_sends_number_with_ninth_digit(): void
    {
        $user = User::factory()->create(['phone_number' => '551187654321']);

        $response = $this->getJson('/api/v1/webhooks/verify-user?phone_number=5511987654321', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_exact_match_takes_priority_over_variant(): void
    {
        User::factory()->create(['phone_number' => '5511987654321']);
        $exactUser = User::factory()->create(['phone_number' => '551187654321']);

        $response = $this->getJson('/api/v1/webhooks/verify-user?phone_number=551187654321', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $exactUser->id);
    }

    public function test_does_not_generate_variant_for_non_brazilian_number(): void
    {
        User::factory()->create(['phone_number' => '1234567890123']);

        $response = $this->getJson('/api/v1/webhooks/verify-user?phone_number=123456789013', [
            'X-Webhook-Token' => $this->webhookToken,
        ]);

        $response->assertNotFound();
    }
}
