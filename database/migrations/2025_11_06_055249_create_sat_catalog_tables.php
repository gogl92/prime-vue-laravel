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
        // 1. Product Keys (catalogo_producto_servicio)
        Schema::create('sat_product_keys', function (Blueprint $table) {
            $table->id();
            $table->string('c_producto_servicio')->unique();
            $table->text('descripcion');
            $table->string('incluir_iva_trasladado');
            $table->string('incluir_ieps_trasladado');
            $table->string('complemento_que_debe_incluir')->nullable();
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->string('tipo');
            $table->string('division');
            $table->string('division_descripcion');
            $table->string('grupo');
            $table->string('grupo_descripcion');
            $table->string('clase');
            $table->string('clase_descripcion');
            $table->timestamps();

            $table->index('c_producto_servicio');
            $table->index('tipo');
        });

        // 2. Unit Keys (catalogo_unidad_de_medida)
        Schema::create('sat_unit_keys', function (Blueprint $table) {
            $table->id();
            $table->string('c_unidad_de_medida')->unique();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->text('nota')->nullable();
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->string('simbolo')->nullable();
            $table->timestamps();

            $table->index('c_unidad_de_medida');
        });

        // 3. Payment Forms (catalogo_forma_de_pago)
        Schema::create('sat_payment_forms', function (Blueprint $table) {
            $table->id();
            $table->string('c_forma_de_pago')->unique();
            $table->string('descripcion');
            $table->string('bancarizado');
            $table->string('numero_operacion')->nullable();
            $table->string('rfc_emisor_cuenta_ordenante')->nullable();
            $table->string('cuenta_ordenante')->nullable();
            $table->string('patron_cuenta_ordenante')->nullable();
            $table->string('rfc_emisor_cuenta_beneficiario')->nullable();
            $table->string('cuenta_beneficiario')->nullable();
            $table->string('patron_cuenta_beneficiaria')->nullable();
            $table->string('tipo_cadena_pago')->nullable();
            $table->string('banco_emisor_caso_extranjero')->nullable();
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_forma_de_pago');
        });

        // 4. Payment Methods (catalogo_metodo_de_pago)
        Schema::create('sat_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('c_metodo_de_pago')->unique();
            $table->string('descripcion');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_metodo_de_pago');
        });

        // 5. CFDI Uses (catalogo_uso_cfdi)
        Schema::create('sat_cfdi_uses', function (Blueprint $table) {
            $table->id();
            $table->string('c_uso_cfdi')->unique();
            $table->string('descripcion');
            $table->string('aplica_tipo_persona_fisica');
            $table->string('aplica_tipo_persona_moral');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_uso_cfdi');
        });

        // 6. Currencies (catalogo_moneda)
        Schema::create('sat_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('c_moneda')->unique();
            $table->string('descripcion');
            $table->integer('decimales');
            $table->decimal('porcentaje_variacion', 10, 4);
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_moneda');
        });

        // 7. Tax Regimes (catalogo_regimen_fiscal)
        Schema::create('sat_tax_regimes', function (Blueprint $table) {
            $table->id();
            $table->string('c_regimen_fiscal')->unique();
            $table->string('descripcion');
            $table->string('aplica_tipo_persona_fisica');
            $table->string('aplica_tipo_persona_moral');
            $table->timestamps();

            $table->index('c_regimen_fiscal');
        });

        // 8. Countries (catalogo_pais)
        Schema::create('sat_countries', function (Blueprint $table) {
            $table->id();
            $table->string('c_pais')->unique();
            $table->string('descripcion');
            $table->string('formato_codigo_postal')->nullable();
            $table->string('formato_registro_identidad_tributaria')->nullable();
            $table->string('validacion_registro_identidad_tributaria')->nullable();
            $table->string('agrupaciones')->nullable();
            $table->timestamps();

            $table->index('c_pais');
        });

        // 9. Tax Types (catalogo_impuesto)
        Schema::create('sat_tax_types', function (Blueprint $table) {
            $table->id();
            $table->string('c_impuesto')->unique();
            $table->string('descripcion');
            $table->string('retencion');
            $table->string('traslado');
            $table->string('ambos');
            $table->timestamps();

            $table->index('c_impuesto');
        });

        // 10. Tax Rates (catalogo_tasa_o_cuota)
        Schema::create('sat_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('c_rango');
            $table->string('minimo');
            $table->string('valor');
            $table->string('c_impuesto');
            $table->string('c_tipo_factor');
            $table->string('retencion');
            $table->string('traslado');
            $table->timestamps();

            $table->index('c_impuesto');
            $table->index('c_tipo_factor');
        });

        // 11. Relation Types (catalogo_tipos_de_relacion)
        Schema::create('sat_relation_types', function (Blueprint $table) {
            $table->id();
            $table->string('c_tipo_de_relacion')->unique();
            $table->string('descripcion');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_tipo_de_relacion');
        });

        // 12. Postal Codes (catalogo_codigo_postal)
        Schema::create('sat_postal_codes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_postal');
            $table->string('estado');
            $table->string('municipio');
            $table->string('localidad');
            $table->timestamps();

            $table->index('codigo_postal');
            $table->index('estado');
        });

        // 13. Payment Form Services (c_FormaPagoServ)
        Schema::create('sat_payment_form_services', function (Blueprint $table) {
            $table->id();
            $table->string('c_periodicidad')->unique();
            $table->string('descripcion');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_periodicidad');
        });

        // 14. Periodicities (c_Periodicidad)
        Schema::create('sat_periodicities', function (Blueprint $table) {
            $table->id();
            $table->string('c_periodicidad')->unique();
            $table->string('descripcion');
            $table->string('complemento_que_lo_usa');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_periodicidad');
        });

        // 15. Withholding Taxes (c_Retenciones)
        Schema::create('sat_withholding_taxes', function (Blueprint $table) {
            $table->id();
            $table->string('c_retenciones')->unique();
            $table->string('descripcion');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_retenciones');
        });

        // 16. Service Subtypes (c_SubTipoServ)
        Schema::create('sat_service_subtypes', function (Blueprint $table) {
            $table->id();
            $table->string('c_sub_tipo_serv')->unique();
            $table->string('descripcion');
            $table->string('tipo_de_servicio');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_sub_tipo_serv');
            $table->index('tipo_de_servicio');
        });

        // 17. Tax Rate Amounts (c_TasaCuota)
        Schema::create('sat_tax_rate_amounts', function (Blueprint $table) {
            $table->id();
            $table->string('valor');
            $table->string('impuesto');
            $table->timestamps();

            $table->index('impuesto');
        });

        // 18. Tax Type Complements (c_TipodeImpuesto)
        Schema::create('sat_tax_type_complements', function (Blueprint $table) {
            $table->id();
            $table->string('c_impuesto')->unique();
            $table->string('descripcion');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->timestamps();

            $table->index('c_impuesto');
        });

        // 19. Service Types (c_TipoDeServ)
        Schema::create('sat_service_types', function (Blueprint $table) {
            $table->id();
            $table->string('c_tipo_de_serv')->unique();
            $table->string('descripcion');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->string('isr');
            $table->string('iva');
            $table->string('ejercicio');
            $table->date('fecha_inicio_vigencia_ejercicio');
            $table->date('fecha_fin_vigencia_ejercicio')->nullable();
            $table->timestamps();

            $table->index('c_tipo_de_serv');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sat_service_types');
        Schema::dropIfExists('sat_tax_type_complements');
        Schema::dropIfExists('sat_tax_rate_amounts');
        Schema::dropIfExists('sat_service_subtypes');
        Schema::dropIfExists('sat_withholding_taxes');
        Schema::dropIfExists('sat_periodicities');
        Schema::dropIfExists('sat_payment_form_services');
        Schema::dropIfExists('sat_postal_codes');
        Schema::dropIfExists('sat_relation_types');
        Schema::dropIfExists('sat_tax_rates');
        Schema::dropIfExists('sat_tax_types');
        Schema::dropIfExists('sat_countries');
        Schema::dropIfExists('sat_tax_regimes');
        Schema::dropIfExists('sat_currencies');
        Schema::dropIfExists('sat_cfdi_uses');
        Schema::dropIfExists('sat_payment_methods');
        Schema::dropIfExists('sat_payment_forms');
        Schema::dropIfExists('sat_unit_keys');
        Schema::dropIfExists('sat_product_keys');
    }
};
