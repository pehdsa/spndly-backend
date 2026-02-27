<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PassportClientSeeder::class,
            CategorySeeder::class,
            PaymentMethodSeeder::class,
        ]);

        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@spndly.test',
            'phone_number' => '5511999999999',
            'password' => 'password',
        ]);

        $this->call(ExpenseSeeder::class);
    }
}
