<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ListExpensesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_own_expenses(): void
    {
        $user = User::factory()->create();
        Expense::factory()->count(3)->create(['user_id' => $user->id]);
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/expenses');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'description', 'amount_cents', 'category', 'payment_method', 'created_at'],
                ],
                'meta',
                'links',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_cannot_see_other_users_expenses(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Expense::factory()->count(2)->create(['user_id' => $otherUser->id]);
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/expenses');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_unauthenticated_cannot_list_expenses(): void
    {
        $response = $this->getJson('/api/v1/expenses');

        $response->assertUnauthorized();
    }

    public function test_blocked_user_cannot_list_expenses(): void
    {
        $user = User::factory()->blocked()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/expenses');

        $response->assertForbidden();
    }
}
