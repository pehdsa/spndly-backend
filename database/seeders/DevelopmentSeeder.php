<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            PaymentMethodSeeder::class,
        ]);

        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@spndly.test',
            'password' => 'password',
        ]);
    }
}
