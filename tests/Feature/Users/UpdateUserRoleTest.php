<?php

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UpdateUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => UserRole::Client]);
        Passport::actingAs($admin);

        $response = $this->patchJson("/api/v1/users/{$user->id}/role", [
            'role' => 'ADMIN',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.role', UserRole::Admin->value);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => UserRole::Admin->value,
        ]);
    }

    public function test_admin_cannot_update_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->patchJson("/api/v1/users/{$admin->id}/role", [
            'role' => 'CLIENT',
        ]);

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_update_user_role(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->patchJson("/api/v1/users/{$other->id}/role", [
            'role' => 'ADMIN',
        ]);

        $response->assertForbidden();
    }

    public function test_role_must_be_valid(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Passport::actingAs($admin);

        $response = $this->patchJson("/api/v1/users/{$user->id}/role", [
            'role' => 'INVALID_ROLE',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_role_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Passport::actingAs($admin);

        $response = $this->patchJson("/api/v1/users/{$user->id}/role", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }
}
