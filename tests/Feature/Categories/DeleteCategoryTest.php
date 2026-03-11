<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DeleteCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        Passport::actingAs($admin);

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'Category deleted successfully.']]);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_non_admin_cannot_delete_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Passport::actingAs($user);

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertUnauthorized();
    }
}
