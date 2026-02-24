<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ShowUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_show_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Passport::actingAs($admin);

        $response = $this->getJson("/api/v1/users/{$user->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'phone_number', 'role', 'status', 'viewed_at', 'created_at'],
            ])
            ->assertJson([
                'data' => ['id' => $user->id],
            ]);
    }

    public function test_non_admin_cannot_show_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/users/{$other->id}");

        $response->assertForbidden();
    }

    public function test_returns_404_for_nonexistent_user(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/users/99999');

        $response->assertNotFound();
    }
}
