<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        $paymentMethods = PaymentMethod::all();

        $admin = User::where('email', 'admin@spndly.test')->first();

        $users = User::factory(3)->create([
            'password' => 'password',
        ]);

        if ($admin) {
            $users->prepend($admin);
        }

        foreach ($users as $user) {
            $count = fake()->numberBetween(30, 50);

            Expense::factory($count)->create([
                'user_id' => $user->id,
                'category_id' => fn () => $categories->random()->id,
                'payment_method_id' => fn () => $paymentMethods->random()->id,
                'amount_cents' => fn () => fake()->numberBetween(500, 300000),
                'created_at' => fn () => now()->subDays(fake()->numberBetween(0, 90)),
            ]);
        }
    }
}
