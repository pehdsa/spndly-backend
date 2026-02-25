<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'user@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'If your email is registered, you will receive a password reset link.']]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_returns_success_for_nonexistent_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'If your email is registered, you will receive a password reset link.']]);

        Notification::assertNothingSent();
    }

    public function test_forgot_password_returns_success_for_blocked_user(): void
    {
        Notification::fake();

        User::factory()->blocked()->create(['email' => 'blocked@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'blocked@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'If your email is registered, you will receive a password reset link.']]);

        Notification::assertNothingSent();
    }

    public function test_forgot_password_requires_valid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_requires_email_field(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
