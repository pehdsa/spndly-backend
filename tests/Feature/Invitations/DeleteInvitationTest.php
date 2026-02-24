<?php

namespace Tests\Feature\Invitations;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DeleteInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::factory()->create(['invited_by' => $admin->id]);
        Passport::actingAs($admin);

        $response = $this->deleteJson("/api/v1/invitations/{$invitation->id}");

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'Invitation deleted successfully.']]);

        $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
    }

    public function test_non_admin_cannot_delete_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $invitation = Invitation::factory()->create(['invited_by' => $admin->id]);
        Passport::actingAs($user);

        $response = $this->deleteJson("/api/v1/invitations/{$invitation->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_delete_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::factory()->create(['invited_by' => $admin->id]);

        $response = $this->deleteJson("/api/v1/invitations/{$invitation->id}");

        $response->assertUnauthorized();
    }

    public function test_returns_404_for_nonexistent_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->deleteJson('/api/v1/invitations/99999');

        $response->assertNotFound();
    }
}
