<?php

namespace Tests\Feature\PaymentMethods;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UpdatePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_payment_method(): void
    {
        $admin = User::factory()->admin()->create();
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Old Name']);
        Passport::actingAs($admin);

        $response = $this->putJson("/api/v1/payment-methods/{$paymentMethod->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['name' => 'New Name']]);

        $this->assertDatabaseHas('payment_methods', ['id' => $paymentMethod->id, 'name' => 'New Name']);
    }

    public function test_admin_can_update_payment_method_with_same_name(): void
    {
        $admin = User::factory()->admin()->create();
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Pix']);
        Passport::actingAs($admin);

        $response = $this->putJson("/api/v1/payment-methods/{$paymentMethod->id}", [
            'name' => 'Pix',
            'description' => 'Updated description',
        ]);

        $response->assertOk();
    }

    public function test_non_admin_cannot_update_payment_method(): void
    {
        $user = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/payment-methods/{$paymentMethod->id}", ['name' => 'New']);

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_update_payment_method(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->putJson("/api/v1/payment-methods/{$paymentMethod->id}", ['name' => 'New']);

        $response->assertUnauthorized();
    }

    public function test_cannot_update_payment_method_to_existing_name(): void
    {
        $admin = User::factory()->admin()->create();
        PaymentMethod::factory()->create(['name' => 'Existing']);
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Other']);
        Passport::actingAs($admin);

        $response = $this->putJson("/api/v1/payment-methods/{$paymentMethod->id}", [
            'name' => 'Existing',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
