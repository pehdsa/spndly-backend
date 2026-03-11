<?php

namespace Tests\Feature\PaymentMethods;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CreatePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_payment_method(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/payment-methods', [
            'name' => 'Pix',
            'description' => 'Pagamento via Pix',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'created_at'],
            ]);

        $this->assertDatabaseHas('payment_methods', ['name' => 'Pix']);
    }

    public function test_admin_can_create_payment_method_without_description(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/payment-methods', ['name' => 'Dinheiro']);

        $response->assertCreated();
        $this->assertDatabaseHas('payment_methods', ['name' => 'Dinheiro', 'description' => null]);
    }

    public function test_non_admin_cannot_create_payment_method(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/payment-methods', ['name' => 'Test']);

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_create_payment_method(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', ['name' => 'Test']);

        $response->assertUnauthorized();
    }

    public function test_payment_method_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        PaymentMethod::factory()->create(['name' => 'Pix']);
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/payment-methods', ['name' => 'Pix']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_payment_method_name_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/payment-methods', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_creating_payment_method_with_soft_deleted_name_restores_it(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Pix', 'description' => 'Descrição antiga']);
        $originalId = $paymentMethod->id;
        $paymentMethod->delete();

        $response = $this->postJson('/api/v1/payment-methods', [
            'name' => 'Pix',
            'description' => 'Descrição nova',
        ]);

        $response->assertCreated();
        $this->assertEquals($originalId, $response->json('data.id'));
        $this->assertEquals('Descrição nova', $response->json('data.description'));
        $this->assertDatabaseHas('payment_methods', [
            'id' => $originalId,
            'name' => 'Pix',
            'deleted_at' => null,
        ]);
    }

    public function test_restored_payment_method_preserves_existing_expense_relationship(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Passport::actingAs($admin);

        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Pix']);
        $category = Category::factory()->create();

        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 5000,
        ]);

        $paymentMethod->delete();

        $response = $this->postJson('/api/v1/payment-methods', ['name' => 'Pix']);

        $response->assertCreated();
        $this->assertEquals($paymentMethod->id, $response->json('data.id'));
        $this->assertEquals($paymentMethod->id, $expense->fresh()->payment_method_id);
    }
}
