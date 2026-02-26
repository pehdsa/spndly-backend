<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Alimentação',
            'Moradia',
            'Transporte',
            'Contas e Serviços',
            'Saúde',
            'Educação',
            'Lazer',
            'Investimentos',
            'Outros',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
