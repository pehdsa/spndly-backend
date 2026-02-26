<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UpdateCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Old Name']);
        Passport::actingAs($admin);

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['name' => 'New Name']]);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_admin_can_update_category_with_same_name(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Alimentação']);
        Passport::actingAs($admin);

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Alimentação',
            'description' => 'Updated description',
        ]);

        $response->assertOk();
    }

    public function test_non_admin_cannot_update_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/categories/{$category->id}", ['name' => 'New']);

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/categories/{$category->id}", ['name' => 'New']);

        $response->assertUnauthorized();
    }

    public function test_cannot_update_category_to_existing_name(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Existing']);
        $category = Category::factory()->create(['name' => 'Other']);
        Passport::actingAs($admin);

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Existing',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
