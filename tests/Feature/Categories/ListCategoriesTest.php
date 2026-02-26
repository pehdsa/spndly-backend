<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ListCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_unauthenticated_cannot_list_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertUnauthorized();
    }

    public function test_blocked_user_cannot_list_categories(): void
    {
        $user = User::factory()->blocked()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/categories');

        $response->assertForbidden();
    }

    public function test_soft_deleted_categories_are_not_listed(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(2)->create();
        $deleted = Category::factory()->create();
        $deleted->delete();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
