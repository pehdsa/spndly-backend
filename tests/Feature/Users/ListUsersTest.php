<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ListUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'role', 'status', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/users');

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_list_users(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertUnauthorized();
    }

    public function test_admin_can_search_users_by_name(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/users?search=John');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('John Doe', $response->json('data.0.name'));
    }

    public function test_admin_can_search_users_by_email(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
        User::factory()->create(['email' => 'john@example.com']);
        User::factory()->create(['email' => 'jane@example.com']);
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/users?search=john');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('john@example.com', $response->json('data.0.email'));
    }

    public function test_blocked_user_cannot_list_users(): void
    {
        $admin = User::factory()->admin()->blocked()->create();
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/users');

        $response->assertForbidden();
    }
}
