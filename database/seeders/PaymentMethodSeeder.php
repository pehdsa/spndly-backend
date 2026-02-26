<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethods = [
            'Dinheiro',
            'Cartão Débito',
            'Cartão Crédito',
            'Boleto',
            'Pix',
            'Transferência',
            'Outros',
        ];

        foreach ($paymentMethods as $name) {
            PaymentMethod::firstOrCreate(['name' => $name]);
        }
    }
}
