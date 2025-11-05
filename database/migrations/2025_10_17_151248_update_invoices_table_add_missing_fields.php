<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add temporary column and new columns
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->after('id')->nullable();
            $table->foreignId('issuer_id')->after('client_id')->nullable()->constrained('clients')->onDelete('set null');

            // Add CFDI fields
            $table->string('cfdi_type')->default('Factura');
            $table->string('order_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('payment_form')->nullable();
            $table->boolean('send_email')->default(true);
            $table->string('payment_method')->nullable();
            $table->string('cfdi_use')->nullable();
            $table->string('series')->nullable();
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->string('currency')->default('MXN');
            $table->text('comments')->nullable();
        });

        // Step 2: Migrate existing invoice data to clients table and link them
        $invoices = DB::table('invoices')->get();
        foreach ($invoices as $invoice) {
            $clientId = DB::table('clients')->insertGetId([
                'name' => $invoice->name,
                'email' => $invoice->email,
                'phone' => $invoice->phone,
                'address' => $invoice->address ?? null,
                'city' => $invoice->city ?? null,
                'state' => $invoice->state ?? null,
                'zip' => $invoice->zip ?? null,
                'country' => $invoice->country ?? null,
                'is_supplier' => false,
                'is_issuer' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update invoice with client_id
            DB::table('invoices')->where('id', $invoice->id)->update(['client_id' => $clientId]);
        }

        // Step 3: Make client_id required and add foreign key, then remove old columns
        Schema::table('invoices', function (Blueprint $table) {
            // Make client_id NOT NULL
            $table->unsignedBigInteger('client_id')->nullable(false)->change();

            // Add foreign key constraint
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');

            // Remove old address fields
            $table->dropColumn([
                'name',
                'email',
                'phone',
                'address',
                'city',
                'state',
                'zip',
                'country',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add back old address fields
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('zip');
            $table->string('country');

            // Remove new fields
            $table->dropForeign(['client_id']);
            $table->dropForeign(['issuer_id']);
            $table->dropColumn([
                'client_id',
                'issuer_id',
                'cfdi_type',
                'order_number',
                'invoice_date',
                'payment_form',
                'send_email',
                'payment_method',
                'cfdi_use',
                'series',
                'exchange_rate',
                'currency',
                'comments'
            ]);
        });
    }
};
