<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        Invoice::factory(10)->create();

        // Seed products and attach them to invoices
        $this->call(ProductSeeder::class);

        // Seed payments for invoices
        $this->call(PaymentSeeder::class);
    }
}
