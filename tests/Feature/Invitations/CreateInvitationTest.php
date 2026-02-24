<?php

namespace Tests\Feature\Invitations;

use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CreateInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_invitation(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['newuser@example.com'],
            'role' => 'ADMIN',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'sent' => [['id', 'email', 'role', 'status', 'expires_at', 'used_at', 'invited_by', 'created_at']],
                    'failed',
                ],
            ]);

        $this->assertDatabaseHas('invitations', [
            'email' => 'newuser@example.com',
            'role' => 'ADMIN',
            'invited_by' => $admin->id,
        ]);

        Notification::assertSentOnDemand(InvitationNotification::class);
    }

    public function test_admin_can_create_batch_invitations(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['user1@example.com', 'user2@example.com'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'failed' => [],
                ],
            ]);

        $this->assertCount(2, $response->json('data.sent'));
    }

    public function test_failed_emails_are_returned_without_exception(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existing@example.com']);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['existing@example.com', 'new@example.com'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();

        $this->assertCount(1, $response->json('data.sent'));
        $this->assertCount(1, $response->json('data.failed'));
        $this->assertEquals('existing@example.com', $response->json('data.failed.0.email'));
    }

    public function test_non_admin_cannot_create_invitation(): void
    {
        $client = User::factory()->create();
        Passport::actingAs($client);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['newuser@example.com'],
            'role' => 'CLIENT',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_create_invitation(): void
    {
        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['newuser@example.com'],
            'role' => 'CLIENT',
        ]);

        $response->assertUnauthorized();
    }

    public function test_cannot_invite_email_with_existing_active_user(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existing@example.com']);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['existing@example.com'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();
        $this->assertCount(0, $response->json('data.sent'));
        $this->assertCount(1, $response->json('data.failed'));
    }

    public function test_cannot_invite_email_with_valid_pending_invitation(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        Invitation::factory()->create([
            'email' => 'pending@example.com',
            'invited_by' => $admin->id,
        ]);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['pending@example.com'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();
        $this->assertCount(0, $response->json('data.sent'));
        $this->assertCount(1, $response->json('data.failed'));
    }

    public function test_can_invite_email_with_expired_invitation(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        Invitation::factory()->expired()->create([
            'email' => 'expired@example.com',
            'invited_by' => $admin->id,
        ]);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['expired@example.com'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();
        $this->assertCount(1, $response->json('data.sent'));
    }

    public function test_can_invite_email_with_used_invitation(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        Invitation::factory()->used()->create([
            'email' => 'used@example.com',
            'invited_by' => $admin->id,
        ]);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['used@example.com'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();
        $this->assertCount(1, $response->json('data.sent'));
    }

    public function test_invitation_requires_valid_role(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => ['newuser@example.com'],
            'role' => 'INVALID_ROLE',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_invitation_requires_emails(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'role' => 'CLIENT',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['emails']);
    }

    public function test_invitation_emails_must_be_array(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'emails' => 'notanarray@example.com',
            'role' => 'CLIENT',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['emails']);
    }
}
