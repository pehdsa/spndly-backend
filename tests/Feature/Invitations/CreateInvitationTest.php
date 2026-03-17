<?php

namespace Tests\Feature\Invitations;

use App\Jobs\SendInvitationToN8nWebhookJob;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CreateInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_invitation(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999999'],
            'role' => 'ADMIN',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'sent' => [['id', 'phone_number', 'role', 'status', 'expires_at', 'used_at', 'invited_by', 'created_at']],
                    'failed',
                ],
            ]);

        $this->assertDatabaseHas('invitations', [
            'phone_number' => '5567999999999',
            'role' => 'ADMIN',
            'invited_by' => $admin->id,
        ]);

        Queue::assertPushed(SendInvitationToN8nWebhookJob::class);
    }

    public function test_admin_can_create_batch_invitations(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999991', '5567999999992'],
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

    public function test_failed_phone_numbers_are_returned_without_exception(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        User::factory()->create(['phone_number' => '5567999999999']);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999999', '5567888888888'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();

        $this->assertCount(1, $response->json('data.sent'));
        $this->assertCount(1, $response->json('data.failed'));
        $this->assertEquals('5567999999999', $response->json('data.failed.0.phone_number'));
    }

    public function test_non_admin_cannot_create_invitation(): void
    {
        $client = User::factory()->create();
        Passport::actingAs($client);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999999'],
            'role' => 'CLIENT',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_create_invitation(): void
    {
        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999999'],
            'role' => 'CLIENT',
        ]);

        $response->assertUnauthorized();
    }

    public function test_cannot_invite_phone_number_with_existing_active_user(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        User::factory()->create(['phone_number' => '5567999999999']);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999999'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();
        $this->assertCount(0, $response->json('data.sent'));
        $this->assertCount(1, $response->json('data.failed'));
    }

    public function test_cannot_invite_phone_number_with_valid_pending_invitation(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        Invitation::factory()->create([
            'phone_number' => '5567999999999',
            'invited_by' => $admin->id,
        ]);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999999'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();
        $this->assertCount(0, $response->json('data.sent'));
        $this->assertCount(1, $response->json('data.failed'));
    }

    public function test_can_invite_phone_number_with_expired_invitation(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        Invitation::factory()->expired()->create([
            'phone_number' => '5567999999999',
            'invited_by' => $admin->id,
        ]);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999999'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();
        $this->assertCount(1, $response->json('data.sent'));
    }

    public function test_can_invite_phone_number_with_used_invitation(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        Invitation::factory()->used()->create([
            'phone_number' => '5567999999999',
            'invited_by' => $admin->id,
        ]);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999999'],
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
            'phone_numbers' => ['5567999999999'],
            'role' => 'INVALID_ROLE',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_invitation_requires_phone_numbers(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'role' => 'CLIENT',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_numbers']);
    }

    public function test_invitation_phone_numbers_must_be_array(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => '5567999999999',
            'role' => 'CLIENT',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_numbers']);
    }

    public function test_phone_number_is_normalized_with_country_code(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['67999999999'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('invitations', [
            'phone_number' => '5567999999999',
        ]);
    }

    public function test_phone_number_already_with_country_code_is_not_duplicated(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['5567999999999'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('invitations', [
            'phone_number' => '5567999999999',
        ]);
    }

    public function test_duplicate_normalized_phone_number_is_blocked(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        Invitation::factory()->create([
            'phone_number' => '5567999999999',
            'invited_by' => $admin->id,
        ]);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['67999999999'],
            'role' => 'CLIENT',
        ]);

        $response->assertCreated();
        $this->assertCount(0, $response->json('data.sent'));
        $this->assertCount(1, $response->json('data.failed'));
    }

    public function test_invalid_phone_number_format_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['12345'],
            'role' => 'CLIENT',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_numbers.0']);
    }

    public function test_phone_number_with_letters_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/invitations', [
            'phone_numbers' => ['abc'],
            'role' => 'CLIENT',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_numbers.0']);
    }
}
