<?php

namespace Tests\Feature\PaymentMethods;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ShowPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_show_payment_method(): void
    {
        $user = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $paymentMethod->id,
                    'name' => $paymentMethod->name,
                ],
            ]);
    }

    public function test_unauthenticated_cannot_show_payment_method(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->getJson("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertUnauthorized();
    }
}
