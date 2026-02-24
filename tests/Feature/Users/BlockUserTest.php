<?php

namespace Tests\Feature\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class BlockUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_block_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Passport::actingAs($admin);

        $response = $this->postJson("/api/v1/users/{$user->id}/block");

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'User blocked successfully.']]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => UserStatus::Blocked->value,
        ]);
    }

    public function test_admin_cannot_block_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson("/api/v1/users/{$admin->id}/block");

        $response->assertForbidden();
    }

    public function test_block_revokes_user_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Passport::actingAs($admin);

        $this->postJson("/api/v1/users/{$user->id}/block");

        $this->assertDatabaseMissing('oauth_access_tokens', [
            'user_id' => $user->id,
            'revoked' => false,
        ]);
    }

    public function test_admin_can_unblock_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->blocked()->create();
        Passport::actingAs($admin);

        $response = $this->postJson("/api/v1/users/{$user->id}/unblock");

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'User unblocked successfully.']]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => UserStatus::Active->value,
        ]);
    }

    public function test_admin_cannot_unblock_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson("/api/v1/users/{$admin->id}/unblock");

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_block_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/users/{$other->id}/block");

        $response->assertForbidden();
    }
}
