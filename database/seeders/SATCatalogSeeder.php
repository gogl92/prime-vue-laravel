<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SATCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding SAT Catalogs...');

        // Disable foreign key checks and truncate tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'sat_product_keys',
            'sat_unit_keys',
            'sat_payment_forms',
            'sat_payment_methods',
            'sat_cfdi_uses',
            'sat_currencies',
            'sat_tax_regimes',
            'sat_countries',
            'sat_tax_types',
            'sat_tax_rates',
            'sat_relation_types',
            'sat_postal_codes',
            'sat_payment_form_services',
            'sat_periodicities',
            'sat_withholding_taxes',
            'sat_service_subtypes',
            'sat_tax_rate_amounts',
            'sat_tax_type_complements',
            'sat_service_types',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        // Seed each catalog
        $this->seedProductKeys();
        $this->seedUnitKeys();
        $this->seedPaymentForms();
        $this->seedPaymentMethods();
        $this->seedCFDIUses();
        $this->seedCurrencies();
        $this->seedTaxRegimes();
        $this->seedCountries();
        $this->seedTaxTypes();
        $this->seedTaxRates();
        $this->seedRelationTypes();
        $this->seedPostalCodes();
        $this->seedPaymentFormServices();
        $this->seedPeriodicities();
        $this->seedWithholdingTaxes();
        $this->seedServiceSubtypes();
        $this->seedTaxRateAmounts();
        $this->seedTaxTypeComplements();
        $this->seedServiceTypes();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('SAT Catalogs seeded successfully!');
    }

    private function seedProductKeys(): void
    {
        $this->command->info('Seeding Product Keys...');
        $path = storage_path('catalogos sat/catalogo_producto_servicio.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_producto_servicio' => $row[1] ?? null,
                'descripcion' => $row[2] ?? null,
                'incluir_iva_trasladado' => $row[3] ?? null,
                'incluir_ieps_trasladado' => $row[4] ?? null,
                'complemento_que_debe_incluir' => $row[5] ?: null,
                'fecha_inicio_vigencia' => $this->parseDate($row[6] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[7] ?? null),
                'tipo' => $row[8] ?? null,
                'division' => $row[9] ?? null,
                'division_descripcion' => $row[10] ?? null,
                'grupo' => $row[11] ?? null,
                'grupo_descripcion' => $row[12] ?? null,
                'clase' => $row[13] ?? null,
                'clase_descripcion' => $row[14] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_product_keys')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_product_keys')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Product Keys seeded.');
    }

    private function seedUnitKeys(): void
    {
        $this->command->info('Seeding Unit Keys...');
        $path = storage_path('catalogos sat/catalogo_unidad_de_medida.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_unidad_de_medida' => $row[1] ?? null,
                'nombre' => $row[2] ?? null,
                'descripcion' => $row[3] ?? null,
                'nota' => $row[4] ?: null,
                'fecha_inicio_vigencia' => $this->parseDate($row[5] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[6] ?? null),
                'simbolo' => $row[7] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_unit_keys')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_unit_keys')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Unit Keys seeded.');
    }

    private function seedPaymentForms(): void
    {
        $this->command->info('Seeding Payment Forms...');
        $path = storage_path('catalogos sat/catalogo_forma_de_pago.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_forma_de_pago' => $row[1] ?? null,
                'descripcion' => $row[2] ?? null,
                'bancarizado' => $row[3] ?? null,
                'numero_operacion' => $row[4] ?: null,
                'rfc_emisor_cuenta_ordenante' => $row[5] ?: null,
                'cuenta_ordenante' => $row[6] ?: null,
                'patron_cuenta_ordenante' => $row[7] ?: null,
                'rfc_emisor_cuenta_beneficiario' => $row[8] ?: null,
                'cuenta_beneficiario' => $row[9] ?: null,
                'patron_cuenta_beneficiaria' => $row[10] ?: null,
                'tipo_cadena_pago' => $row[11] ?: null,
                'banco_emisor_caso_extranjero' => $row[12] ?: null,
                'fecha_inicio_vigencia' => $this->parseDate($row[13] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[14] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_payment_forms')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_payment_forms')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Payment Forms seeded.');
    }

    private function seedPaymentMethods(): void
    {
        $this->command->info('Seeding Payment Methods...');
        $path = storage_path('catalogos sat/catalogo_metodo_de_pago.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_metodo_de_pago' => $row[1] ?? null,
                'descripcion' => $row[2] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[3] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[4] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_payment_methods')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_payment_methods')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Payment Methods seeded.');
    }

    private function seedCFDIUses(): void
    {
        $this->command->info('Seeding CFDI Uses...');
        $path = storage_path('catalogos sat/catalogo_uso_cfdi.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_uso_cfdi' => $row[1] ?? null,
                'descripcion' => $row[2] ?? null,
                'aplica_tipo_persona_fisica' => $row[3] ?? null,
                'aplica_tipo_persona_moral' => $row[4] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[5] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[6] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_cfdi_uses')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_cfdi_uses')->insert($batch);
        }

        fclose($handle);
        $this->command->info('CFDI Uses seeded.');
    }

    private function seedCurrencies(): void
    {
        $this->command->info('Seeding Currencies...');
        $path = storage_path('catalogos sat/catalogo_moneda.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_moneda' => $row[1] ?? null,
                'descripcion' => $row[2] ?? null,
                'decimales' => $row[3] ?? null,
                'porcentaje_variacion' => $row[4] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[5] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[6] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_currencies')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_currencies')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Currencies seeded.');
    }

    private function seedTaxRegimes(): void
    {
        $this->command->info('Seeding Tax Regimes...');
        $path = storage_path('catalogos sat/catalogo_regimen_fiscal.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_regimen_fiscal' => $row[0] ?? null,
                'descripcion' => $row[1] ?? null,
                'aplica_tipo_persona_fisica' => $row[2] ?? null,
                'aplica_tipo_persona_moral' => $row[3] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_tax_regimes')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_tax_regimes')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Tax Regimes seeded.');
    }

    private function seedCountries(): void
    {
        $this->command->info('Seeding Countries...');
        $path = storage_path('catalogos sat/catalogo_pais.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_pais' => $row[1] ?? null,
                'descripcion' => $row[2] ?? null,
                'formato_codigo_postal' => $row[3] ?: null,
                'formato_registro_identidad_tributaria' => $row[4] ?: null,
                'validacion_registro_identidad_tributaria' => $row[5] ?: null,
                'agrupaciones' => $row[6] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_countries')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_countries')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Countries seeded.');
    }

    private function seedTaxTypes(): void
    {
        $this->command->info('Seeding Tax Types...');
        $path = storage_path('catalogos sat/catalogo_impuesto.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_impuesto' => $row[1] ?? null,
                'descripcion' => $row[2] ?? null,
                'retencion' => $row[3] ?? null,
                'traslado' => $row[4] ?? null,
                'ambos' => $row[5] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_tax_types')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_tax_types')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Tax Types seeded.');
    }

    private function seedTaxRates(): void
    {
        $this->command->info('Seeding Tax Rates...');
        $path = storage_path('catalogos sat/catalogo_tasa_o_cuota.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'tipo_factor' => $row[1] ?? null,
                'rango_origen_inferior' => $row[2] ?: null,
                'rango_origen_superior' => $row[3] ?: null,
                'impuesto' => $row[4] ?? null,
                'factor' => $row[5] ?: null,
                'traslado' => $row[6] ?? null,
                'retencion' => $row[7] ?? null,
                'maximo_decimales' => $row[8] ?: null,
                'fecha_inicio_vigencia' => $this->parseDate($row[9] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[10] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_tax_rates')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_tax_rates')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Tax Rates seeded.');
    }

    private function seedRelationTypes(): void
    {
        $this->command->info('Seeding Relation Types...');
        $path = storage_path('catalogos sat/catalogo_tipos_de_relacion.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_tipo_de_relacion' => $row[0] ?? null,
                'descripcion' => $row[1] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[2] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[3] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_relation_types')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_relation_types')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Relation Types seeded.');
    }

    private function seedPostalCodes(): void
    {
        $this->command->info('Seeding Postal Codes...');
        $path = storage_path('catalogos sat/catalogo_codigo_postal.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_codigo_postal' => $row[0] ?? null,
                'c_estado' => $row[1] ?? null,
                'c_municipio' => $row[2] ?? null,
                'c_localidad' => $row[3] ?? null,
                'estimulo_frontera' => $row[4] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_postal_codes')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_postal_codes')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Postal Codes seeded.');
    }

    private function seedPaymentFormServices(): void
    {
        $this->command->info('Seeding Payment Form Services...');
        $path = storage_path('catalogos sat/c_FormaPagoServ.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_forma_pago_serv' => $row[0] ?? null,
                'descripcion' => $row[1] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[2] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[3] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_payment_form_services')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_payment_form_services')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Payment Form Services seeded.');
    }

    private function seedPeriodicities(): void
    {
        $this->command->info('Seeding Periodicities...');
        $path = storage_path('catalogos sat/c_Periodicidad.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_periodicidad' => $row[0] ?? null,
                'descripcion' => $row[1] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[3] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[4] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_periodicities')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_periodicities')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Periodicities seeded.');
    }

    private function seedWithholdingTaxes(): void
    {
        $this->command->info('Seeding Withholding Taxes...');
        $path = storage_path('catalogos sat/c_Retenciones.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_impuesto_ret' => $row[0] ?? null,
                'descripcion' => $row[1] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[2] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[3] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_withholding_taxes')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_withholding_taxes')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Withholding Taxes seeded.');
    }

    private function seedServiceSubtypes(): void
    {
        $this->command->info('Seeding Service Subtypes...');
        $path = storage_path('catalogos sat/c_SubTipoServ.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_sub_tipo_serv' => $row[0] ?? null,
                'descripcion' => $row[1] ?? null,
                'tipo_de_servicio' => $row[2] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[3] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[4] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_service_subtypes')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_service_subtypes')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Service Subtypes seeded.');
    }

    private function seedTaxRateAmounts(): void
    {
        $this->command->info('Seeding Tax Rate Amounts...');
        $path = storage_path('catalogos sat/c_TasaCuota.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'valor' => $row[0] ?? null,
                'impuesto' => $row[1] ?? null,
                'fecha_inicio_vigencia' => null,
                'fecha_fin_vigencia' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_tax_rate_amounts')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_tax_rate_amounts')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Tax Rate Amounts seeded.');
    }

    private function seedTaxTypeComplements(): void
    {
        $this->command->info('Seeding Tax Type Complements...');
        $path = storage_path('catalogos sat/c_TipodeImpuesto.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_impuesto' => $row[0] ?? null,
                'descripcion' => $row[1] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[2] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[3] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_tax_type_complements')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_tax_type_complements')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Tax Type Complements seeded.');
    }

    private function seedServiceTypes(): void
    {
        $this->command->info('Seeding Service Types...');
        $path = storage_path('catalogos sat/c_TipoDeServ.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // Skip header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'c_tipo_de_serv' => $row[0] ?? null,
                'descripcion' => $row[1] ?? null,
                'fecha_inicio_vigencia' => $this->parseDate($row[2] ?? null),
                'fecha_fin_vigencia' => $this->parseDate($row[3] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_service_types')->insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('sat_service_types')->insert($batch);
        }

        fclose($handle);
        $this->command->info('Service Types seeded.');
    }

    /**
     * Parse date string to Y-m-d format or null
     */
    private function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        // Try different date formats
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $date);
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }
}
