<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Invoice;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 products
        $products = Product::factory(50)->create();

        // Attach random products to existing invoices
        $invoices = Invoice::all();

        foreach ($invoices as $invoice) {
            // Randomly select 1-5 products for each invoice
            $randomProducts = $products->random(rand(1, 5));

            foreach ($randomProducts as $product) {
                // Attach product with random quantity and current price
                $invoice->products()->attach($product->id, [
                    'quantity' => rand(1, 10),
                    'price' => $product->price,
                ]);
            }
        }
    }
}
