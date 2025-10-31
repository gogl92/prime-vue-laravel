<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some issuer companies
        Client::factory()->issuer()->count(3)->create();

        // Create some suppliers
        Client::factory()->supplier()->count(10)->create();

        // Create regular clients (customers)
        Client::factory()->count(20)->create();
    }
}
