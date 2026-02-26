<?php

namespace Tests\Feature\Dashboard;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // --- Auth ---

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertUnauthorized();
    }

    public function test_blocked_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->blocked()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertForbidden();
    }

    // --- Client ---

    public function test_client_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'totals' => ['total_amount', 'total_count'],
                    'top_categories',
                    'top_payment_methods',
                    'recent_expenses',
                ],
            ]);
    }

    public function test_client_sees_only_own_expenses(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
        ]);
        Expense::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 5000,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $this->assertEquals(3000, $response->json('data.totals.total_amount'));
        $this->assertEquals(3, $response->json('data.totals.total_count'));
    }

    public function test_client_user_id_filter_is_ignored(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->count(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
        ]);
        Expense::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 5000,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/dashboard?user_id={$otherUser->id}");

        $response->assertOk();
        $this->assertEquals(2000, $response->json('data.totals.total_amount'));
        $this->assertEquals(2, $response->json('data.totals.total_count'));
    }

    // --- Admin ---

    public function test_admin_sees_all_users_expenses(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->count(3)->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
        ]);
        Expense::factory()->count(2)->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2000,
        ]);

        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $this->assertEquals(7000, $response->json('data.totals.total_amount'));
        $this->assertEquals(5, $response->json('data.totals.total_count'));
    }

    public function test_admin_can_filter_by_user_id(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->count(3)->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
        ]);
        Expense::factory()->count(2)->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 5000,
        ]);

        Passport::actingAs($admin);

        $response = $this->getJson("/api/v1/dashboard?user_id={$user1->id}");

        $response->assertOk();
        $this->assertEquals(3000, $response->json('data.totals.total_amount'));
        $this->assertEquals(3, $response->json('data.totals.total_count'));
    }

    public function test_admin_filter_invalid_user_id_returns_422(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/dashboard?user_id=99999');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    // --- Period ---

    public function test_default_period_is_30_days(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
            'created_at' => now()->subDays(10),
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2000,
            'created_at' => now()->subDays(40),
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $this->assertEquals(1000, $response->json('data.totals.total_amount'));
        $this->assertEquals(1, $response->json('data.totals.total_count'));
    }

    public function test_period_7d_filters_correctly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
            'created_at' => now()->subDays(3),
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2000,
            'created_at' => now()->subDays(10),
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?period=7d');

        $response->assertOk();
        $this->assertEquals(1000, $response->json('data.totals.total_amount'));
        $this->assertEquals(1, $response->json('data.totals.total_count'));
    }

    public function test_period_90d_filters_correctly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
            'created_at' => now()->subDays(60),
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2000,
            'created_at' => now()->subDays(100),
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?period=90d');

        $response->assertOk();
        $this->assertEquals(1000, $response->json('data.totals.total_amount'));
        $this->assertEquals(1, $response->json('data.totals.total_count'));
    }

    public function test_period_12m_filters_correctly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
            'created_at' => now()->subMonths(6),
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2000,
            'created_at' => now()->subMonths(14),
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?period=12m');

        $response->assertOk();
        $this->assertEquals(1000, $response->json('data.totals.total_amount'));
        $this->assertEquals(1, $response->json('data.totals.total_count'));
    }

    public function test_invalid_period_returns_422(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?period=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['period']);
    }

    // --- Response structure ---

    public function test_dashboard_response_structure(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'totals' => ['total_amount', 'total_count'],
                    'top_categories' => [
                        '*' => ['id', 'name', 'total_amount', 'total_count'],
                    ],
                    'top_payment_methods' => [
                        '*' => ['id', 'name', 'total_amount', 'total_count'],
                    ],
                    'recent_expenses' => [
                        '*' => ['id', 'description', 'amount_cents', 'category', 'payment_method', 'created_at'],
                    ],
                ],
            ]);
    }

    public function test_top_categories_ordered_by_total_amount_desc(): void
    {
        $user = User::factory()->create();
        $categoryLow = Category::factory()->create(['name' => 'Low']);
        $categoryHigh = Category::factory()->create(['name' => 'High']);
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $categoryLow->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $categoryHigh->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 5000,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $categories = $response->json('data.top_categories');
        $this->assertEquals('High', $categories[0]['name']);
        $this->assertEquals('Low', $categories[1]['name']);
    }

    public function test_top_payment_methods_ordered_by_total_amount_desc(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $pmLow = PaymentMethod::factory()->create(['name' => 'Low']);
        $pmHigh = PaymentMethod::factory()->create(['name' => 'High']);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $pmLow->id,
            'amount_cents' => 1000,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $pmHigh->id,
            'amount_cents' => 5000,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $methods = $response->json('data.top_payment_methods');
        $this->assertEquals('High', $methods[0]['name']);
        $this->assertEquals('Low', $methods[1]['name']);
    }

    public function test_recent_expenses_returns_last_5_with_relationships(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->count(7)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $recentExpenses = $response->json('data.recent_expenses');
        $this->assertCount(5, $recentExpenses);
        $this->assertArrayHasKey('category', $recentExpenses[0]);
        $this->assertArrayHasKey('payment_method', $recentExpenses[0]);
    }

    // --- Edge cases ---

    public function test_dashboard_returns_zero_totals_when_no_expenses(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.totals.total_amount'));
        $this->assertEquals(0, $response->json('data.totals.total_count'));
        $this->assertEmpty($response->json('data.top_categories'));
        $this->assertEmpty($response->json('data.top_payment_methods'));
        $this->assertEmpty($response->json('data.recent_expenses'));
    }

    public function test_soft_deleted_expenses_are_excluded(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 5000,
        ]);
        $expense->delete();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $this->assertEquals(1000, $response->json('data.totals.total_amount'));
        $this->assertEquals(1, $response->json('data.totals.total_count'));
    }

    // --- Absolute date range (start_date/end_date) ---

    public function test_absolute_date_range_filters_correctly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 1000,
            'created_at' => '2026-01-15 12:00:00',
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 2000,
            'created_at' => '2026-02-15 12:00:00',
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?start_date=2026-01-01&end_date=2026-01-31');

        $response->assertOk();
        $this->assertEquals(1000, $response->json('data.totals.total_amount'));
        $this->assertEquals(1, $response->json('data.totals.total_count'));
    }

    public function test_absolute_date_range_ignores_period_param(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount_cents' => 3000,
            'created_at' => '2026-01-10 12:00:00',
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?start_date=2026-01-01&end_date=2026-01-31&period=7d');

        $response->assertOk();
        $this->assertEquals(3000, $response->json('data.totals.total_amount'));
        $this->assertEquals(1, $response->json('data.totals.total_count'));
    }

    public function test_start_date_requires_end_date(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?start_date=2026-01-01');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_end_date_requires_start_date(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?end_date=2026-01-31');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_end_date_must_be_after_or_equal_start_date(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?start_date=2026-01-31&end_date=2026-01-01');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_invalid_date_format_returns_422(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?start_date=01-01-2026&end_date=31-01-2026');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }
}
