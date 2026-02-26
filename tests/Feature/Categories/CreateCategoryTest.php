<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CreateCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Alimentação',
            'description' => 'Gastos com alimentação',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'created_at'],
            ]);

        $this->assertDatabaseHas('categories', ['name' => 'Alimentação']);
    }

    public function test_admin_can_create_category_without_description(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Outros',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', ['name' => 'Outros', 'description' => null]);
    }

    public function test_non_admin_cannot_create_category(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/categories', ['name' => 'Test']);

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_create_category(): void
    {
        $response = $this->postJson('/api/v1/categories', ['name' => 'Test']);

        $response->assertUnauthorized();
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Alimentação']);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/categories', ['name' => 'Alimentação']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_category_name_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/categories', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
