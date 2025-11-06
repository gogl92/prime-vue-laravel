<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // UUID for CFDI
            $table->uuid('uuid')->nullable()->after('id')->unique();

            // Amounts and financials
            $table->decimal('import', 12, 2)->nullable()->after('exchange_rate')->comment('Total amount');
            $table->decimal('import_usd', 12, 2)->nullable()->after('import')->comment('Total amount in USD');
            $table->decimal('sub_total', 12, 2)->nullable()->after('import_usd');
            $table->decimal('retention_tax', 12, 2)->default(0)->after('sub_total');
            $table->decimal('iva_tax', 12, 2)->default(0)->after('retention_tax');
            $table->decimal('paid', 12, 2)->default(0)->after('iva_tax')->comment('Amount paid');

            // Supplier relationship
            $table->foreignId('supplier_id')->nullable()->after('issuer_id')->constrained('clients')->onDelete('set null');

            // CFDI sender information
            $table->string('sender_name')->nullable()->after('supplier_id');
            $table->string('sender_rfc')->nullable()->after('sender_name')->comment('RFC del emisor');
            $table->string('receipt_rfc')->nullable()->after('sender_rfc')->comment('RFC del receptor');

            // CFDI receipt and complement
            $table->string('receipt_type')->nullable()->after('cfdi_use');
            $table->string('complement_id')->nullable()->after('receipt_type');
            $table->date('complement_date')->nullable()->after('complement_id');

            // Files and documents
            $table->string('pdf')->nullable()->after('complement_date')->comment('Path to PDF file');
            $table->string('xml_path')->nullable()->after('pdf')->comment('Path to XML file');
            $table->text('cfdi_json')->nullable()->after('xml_path')->comment('CFDI data in JSON format');

            // Expense type (without foreign key constraint for now - create expense_types table if needed)
            $table->unsignedBigInteger('expenses_type_id')->nullable()->after('cfdi_json');

            // Status and token
            $table->string('status')->default('pending')->after('expenses_type_id');
            $table->string('token')->nullable()->after('status')->unique();

            // Rename invoice_date to date for consistency
            $table->renameColumn('invoice_date', 'date');

            // Add index for common lookups
            $table->index('status');
            $table->index('date');
            $table->index(['supplier_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['status']);
            $table->dropIndex(['date']);
            $table->dropIndex(['supplier_id', 'date']);

            // Rename date back to invoice_date
            $table->renameColumn('date', 'invoice_date');

            // Drop foreign keys
            $table->dropForeign(['supplier_id']);

            // Drop columns
            $table->dropColumn([
                'uuid',
                'import',
                'import_usd',
                'sub_total',
                'retention_tax',
                'iva_tax',
                'paid',
                'supplier_id',
                'sender_name',
                'sender_rfc',
                'receipt_rfc',
                'receipt_type',
                'complement_id',
                'complement_date',
                'pdf',
                'xml_path',
                'cfdi_json',
                'expenses_type_id',
                'status',
                'token',
            ]);
        });
    }
};
