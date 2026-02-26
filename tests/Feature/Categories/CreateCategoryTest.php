<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentMethod;
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

    public function test_creating_category_with_soft_deleted_name_restores_it(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $category = Category::factory()->create(['name' => 'Alimentação', 'description' => 'Descrição antiga']);
        $originalId = $category->id;
        $category->delete();

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Alimentação',
            'description' => 'Descrição nova',
        ]);

        $response->assertCreated();
        $this->assertEquals($originalId, $response->json('data.id'));
        $this->assertEquals('Descrição nova', $response->json('data.description'));
        $this->assertDatabaseHas('categories', [
            'id' => $originalId,
            'name' => 'Alimentação',
            'deleted_at' => null,
        ]);
    }

    public function test_restored_category_preserves_existing_expense_relationship(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Passport::actingAs($admin);

        $category = Category::factory()->create(['name' => 'Alimentação']);
        $paymentMethod = PaymentMethod::factory()->create();

        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 5000,
        ]);

        $category->delete();

        $response = $this->postJson('/api/v1/categories', ['name' => 'Alimentação']);

        $response->assertCreated();
        $this->assertEquals($category->id, $response->json('data.id'));
        $this->assertEquals($category->id, $expense->fresh()->category_id);
    }
}
