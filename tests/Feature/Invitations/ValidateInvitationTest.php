<?php

namespace Tests\Feature\Invitations;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidateInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_token_returns_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::factory()->create(['invited_by' => $admin->id]);

        $response = $this->getJson("/api/v1/invitations/validate?token={$invitation->token}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'email', 'role', 'status', 'expires_at', 'used_at', 'invited_by', 'created_at'],
            ])
            ->assertJsonPath('data.status', 'VALID');
    }

    public function test_invalid_token_returns_422(): void
    {
        $response = $this->getJson('/api/v1/invitations/validate?token=invalidtoken');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_used_invitation_token_returns_422(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::factory()->used()->create(['invited_by' => $admin->id]);

        $response = $this->getJson("/api/v1/invitations/validate?token={$invitation->token}");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_expired_invitation_token_returns_422(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::factory()->expired()->create(['invited_by' => $admin->id]);

        $response = $this->getJson("/api/v1/invitations/validate?token={$invitation->token}");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_validate_endpoint_is_publicly_accessible(): void
    {
        $response = $this->getJson('/api/v1/invitations/validate?token=sometoken');

        $response->assertUnprocessable();
    }
}
