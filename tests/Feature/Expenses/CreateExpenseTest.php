<?php

namespace Tests\Feature\Expenses;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CreateExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_expense(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/expenses', [
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'description' => 'Almoço',
            'amount_cents' => 2500,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'description', 'amount_cents', 'category', 'payment_method', 'created_at'],
            ]);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2500,
        ]);
    }

    public function test_expense_is_created_for_authenticated_user_not_from_input(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        Passport::actingAs($user);

        $this->postJson('/api/v1/expenses', [
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
            'user_id' => $otherUser->id,
        ]);

        $this->assertDatabaseMissing('expenses', ['user_id' => $otherUser->id]);
        $this->assertDatabaseHas('expenses', ['user_id' => $user->id]);
    }

    public function test_expense_can_be_created_without_description(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/expenses', [
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 500,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('expenses', ['description' => null, 'amount_cents' => 500]);
    }

    public function test_cannot_create_expense_with_deleted_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $category->delete();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/expenses', [
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_cannot_create_expense_with_deleted_payment_method(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $paymentMethod->delete();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/expenses', [
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method_id']);
    }

    public function test_amount_cents_must_be_at_least_1(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/expenses', [
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 0,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_cents']);
    }

    public function test_unauthenticated_cannot_create_expense(): void
    {
        $response = $this->postJson('/api/v1/expenses', []);

        $response->assertUnauthorized();
    }
}
