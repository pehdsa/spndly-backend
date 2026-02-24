<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Passport::actingAs($admin);

        $response = $this->deleteJson("/api/v1/users/{$user->id}");

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'User deleted successfully.']]);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->deleteJson("/api/v1/users/{$admin->id}");

        $response->assertForbidden();
        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->deleteJson("/api/v1/users/{$other->id}");

        $response->assertForbidden();
    }

    public function test_delete_revokes_user_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Passport::actingAs($admin);

        $this->deleteJson("/api/v1/users/{$user->id}");

        $this->assertDatabaseMissing('oauth_access_tokens', [
            'user_id' => $user->id,
            'revoked' => false,
        ]);
    }
}
