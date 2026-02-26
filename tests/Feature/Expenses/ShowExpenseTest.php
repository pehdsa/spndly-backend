<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ShowExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_show_own_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);
        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/expenses/{$expense->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'description', 'amount_cents', 'category', 'payment_method', 'created_at'],
            ])
            ->assertJson(['data' => ['id' => $expense->id]]);
    }

    public function test_user_cannot_show_other_users_expense(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $otherUser->id]);
        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/expenses/{$expense->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_show_expense(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->getJson("/api/v1/expenses/{$expense->id}");

        $response->assertUnauthorized();
    }
}
