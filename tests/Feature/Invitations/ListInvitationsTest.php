<?php

namespace Tests\Feature\Invitations;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ListInvitationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_invitations(): void
    {
        $admin = User::factory()->admin()->create();
        Invitation::factory()->count(3)->create(['invited_by' => $admin->id]);
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/invitations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'email', 'role', 'status', 'expires_at', 'used_at', 'invited_by', 'created_at'],
                ],
                'meta',
                'links',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_non_admin_cannot_list_invitations(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/invitations');

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_list_invitations(): void
    {
        $response = $this->getJson('/api/v1/invitations');

        $response->assertUnauthorized();
    }

    public function test_admin_can_search_invitations_by_email(): void
    {
        $admin = User::factory()->admin()->create();
        Invitation::factory()->create(['email' => 'john@example.com', 'invited_by' => $admin->id]);
        Invitation::factory()->create(['email' => 'jane@example.com', 'invited_by' => $admin->id]);
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/invitations?search=john');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('john@example.com', $response->json('data.0.email'));
    }

    public function test_invitation_status_is_calculated_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        Invitation::factory()->create(['invited_by' => $admin->id]);
        Invitation::factory()->expired()->create(['invited_by' => $admin->id]);
        Invitation::factory()->used()->create(['invited_by' => $admin->id]);
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/invitations');

        $response->assertOk();

        $statuses = collect($response->json('data'))->pluck('status')->toArray();
        $this->assertContains('VALID', $statuses);
        $this->assertContains('EXPIRED', $statuses);
        $this->assertContains('USED', $statuses);
    }
}
