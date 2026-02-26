<?php

namespace Tests\Feature\PaymentMethods;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ListPaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_payment_methods(): void
    {
        $user = User::factory()->create();
        PaymentMethod::factory()->count(3)->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/payment-methods');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_unauthenticated_cannot_list_payment_methods(): void
    {
        $response = $this->getJson('/api/v1/payment-methods');

        $response->assertUnauthorized();
    }

    public function test_soft_deleted_payment_methods_are_not_listed(): void
    {
        $user = User::factory()->create();
        PaymentMethod::factory()->count(2)->create();
        $deleted = PaymentMethod::factory()->create();
        $deleted->delete();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/payment-methods');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
