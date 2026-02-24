<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class PassportClientSeeder extends Seeder
{
    public function __construct(public readonly ClientRepository $clients) {}

    public function run(): void
    {
        $client = $this->clients->createPasswordGrantClient(
            'Password Grant Client',
            confidential: true,
        );

        $this->command->info("PASSPORT_PASSWORD_GRANT_CLIENT_ID={$client->getKey()}");
        $this->command->info("PASSPORT_PASSWORD_GRANT_CLIENT_SECRET={$client->plainSecret}");
    }
}
