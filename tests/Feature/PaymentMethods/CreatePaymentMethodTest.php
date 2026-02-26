<?php

namespace Tests\Feature\PaymentMethods;

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
}
