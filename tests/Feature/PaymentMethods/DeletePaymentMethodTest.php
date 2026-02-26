<?php

namespace Tests\Feature\PaymentMethods;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DeletePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_payment_method(): void
    {
        $admin = User::factory()->admin()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        Passport::actingAs($admin);

        $response = $this->deleteJson("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'Payment method deleted successfully.']]);

        $this->assertSoftDeleted('payment_methods', ['id' => $paymentMethod->id]);
    }

    public function test_non_admin_cannot_delete_payment_method(): void
    {
        $user = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        Passport::actingAs($user);

        $response = $this->deleteJson("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_delete_payment_method(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->deleteJson("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertUnauthorized();
    }
}
