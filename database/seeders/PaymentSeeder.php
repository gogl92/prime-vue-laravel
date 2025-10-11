<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all existing invoices
        $invoices = Invoice::all();

        if ($invoices->isEmpty()) {
            $this->command->warn('No invoices found. Please run InvoiceSeeder first.');
            return;
        }

        // Create 1-5 payments for each invoice
        foreach ($invoices as $invoice) {
            $numberOfPayments = rand(1, 5);

            Payment::factory()
                ->count($numberOfPayments)
                ->for($invoice)
                ->create();
        }

        $this->command->info('Payments seeded successfully!');
    }
}

