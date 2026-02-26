<?php

namespace Tests\Feature\Expenses;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UpdateExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_own_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id, 'amount_cents' => 1000]);
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/expenses/{$expense->id}", [
            'amount_cents' => 2000,
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['amount_cents' => 2000]]);

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount_cents' => 2000]);
    }

    public function test_user_cannot_update_other_users_expense(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $otherUser->id]);
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/expenses/{$expense->id}", ['amount_cents' => 999]);

        $response->assertForbidden();
    }

    public function test_user_can_update_expense_keeping_deleted_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $category->delete();
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/expenses/{$expense->id}", [
            'category_id' => $category->id,
            'description' => 'Updated description',
        ]);

        $response->assertOk();
    }

    public function test_cannot_switch_to_deleted_category(): void
    {
        $user = User::factory()->create();
        $currentCategory = Category::factory()->create();
        $deletedCategory = Category::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $currentCategory->id,
        ]);
        $deletedCategory->delete();
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/expenses/{$expense->id}", [
            'category_id' => $deletedCategory->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_unauthenticated_cannot_update_expense(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->putJson("/api/v1/expenses/{$expense->id}", ['amount_cents' => 999]);

        $response->assertUnauthorized();
    }

    public function test_amount_cents_must_be_at_least_1_on_update(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/expenses/{$expense->id}", ['amount_cents' => 0]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_cents']);
    }
}
