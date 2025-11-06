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
        Schema::table('products', function (Blueprint $table) {
            // CFDI Product/Service fields
            $table->string('clave_prod_serv')->nullable()->after('sku')->comment('Clave de producto o servicio SAT');
            $table->string('clave_unidad')->nullable()->after('clave_prod_serv')->comment('Clave de unidad SAT');
            $table->string('unidad')->nullable()->after('clave_unidad')->comment('Unidad de medida');

            // Financial fields
            $table->decimal('importe', 12, 2)->nullable()->after('price')->comment('Importe total (Cantidad × ValorUnitario)');
            $table->decimal('descuento', 12, 2)->default(0)->after('importe')->comment('Descuento');

            // Customs and property
            $table->string('numero_pedimento')->nullable()->after('descuento')->comment('Número de pedimento aduanal');
            $table->string('cuenta_predial')->nullable()->after('numero_pedimento')->comment('Cuenta predial');

            // Complex CFDI data
            $table->json('partes')->nullable()->after('cuenta_predial')->comment('Partes del producto (JSON)');
            $table->json('complemento')->nullable()->after('partes')->comment('Complemento CFDI (JSON)');

            // Status
            $table->string('status')->default('active')->after('complemento');

            // Indexes
            $table->index('clave_prod_serv');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['clave_prod_serv']);
            $table->dropIndex(['status']);

            // Drop columns
            $table->dropColumn([
                'clave_prod_serv',
                'clave_unidad',
                'unidad',
                'importe',
                'descuento',
                'numero_pedimento',
                'cuenta_predial',
                'partes',
                'complemento',
                'status',
            ]);
        });
    }
};
