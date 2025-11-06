import { Model } from '@tailflow/laravel-orion/lib/model';

/**
 * SAT Catalog: CFDI Types
 */
export class CFDITypes extends Model<{
  id?: number;
  key: string;
  value: string;
}> {
  public $resource(): string {
    return 'cfdi-types';
  }
}

/**
 * SAT Catalog: Product and Service Keys
 * File: catalogo_producto_servicio.csv
 */
export class ProductKeys extends Model<{
  id?: number;
  c_producto_servicio: string;
  descripcion: string;
  incluir_iva_trasladado: string;
  incluir_ieps_trasladado: string;
  complemento_que_debe_incluir: string | null;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
  tipo: string;
  division: string;
  division_descripcion: string;
  grupo: string;
  grupo_descripcion: string;
  clase: string;
  clase_descripcion: string;
}> {
  public $resource(): string {
    return 'product-keys';
  }
}

/**
 * SAT Catalog: Unit of Measure
 * File: catalogo_unidad_de_medida.csv
 */
export class UnitKeys extends Model<{
  id?: number;
  c_unidad_de_medida: string;
  nombre: string;
  descripcion: string | null;
  nota: string | null;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
  simbolo: string | null;
}> {
  public $resource(): string {
    return 'unit-keys';
  }
}

/**
 * SAT Catalog: Payment Form
 * File: catalogo_forma_de_pago.csv
 */
export class PaymentForm extends Model<{
  id?: number;
  c_forma_de_pago: string;
  descripcion: string;
  bancarizado: string;
  numero_operacion: string | null;
  rfc_emisor_cuenta_ordenante: string | null;
  cuenta_ordenante: string | null;
  patron_cuenta_ordenante: string | null;
  rfc_emisor_cuenta_beneficiario: string | null;
  cuenta_beneficiario: string | null;
  patron_cuenta_beneficiaria: string | null;
  tipo_cadena_pago: string | null;
  banco_emisor_caso_extranjero: string | null;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'payment-forms';
  }
}

/**
 * SAT Catalog: Payment Method
 * File: catalogo_metodo_de_pago.csv
 */
export class PaymentMethod extends Model<{
  id?: number;
  c_metodo_de_pago: string;
  descripcion: string;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'payment-methods';
  }
}

/**
 * SAT Catalog: CFDI Use
 * File: catalogo_uso_cfdi.csv
 */
export class CFDIUse extends Model<{
  id?: number;
  c_uso_cfdi: string;
  descripcion: string;
  aplica_tipo_persona_fisica: string;
  aplica_tipo_persona_moral: string;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'cfdi-uses';
  }
}

/**
 * SAT Catalog: Currency
 * File: catalogo_moneda.csv
 */
export class Currency extends Model<{
  id?: number;
  c_moneda: string;
  descripcion: string;
  decimales: number;
  porcentaje_variacion: number;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'currencies';
  }
}

/**
 * SAT Catalog: Tax Regime
 * File: catalogo_regimen_fiscal.csv
 */
export class TaxRegime extends Model<{
  id?: number;
  c_regimen_fiscal: string;
  descripcion: string;
  aplica_tipo_persona_fisica: string;
  aplica_tipo_persona_moral: string;
}> {
  public $resource(): string {
    return 'tax-regimes';
  }
}

/**
 * SAT Catalog: Country
 * File: catalogo_pais.csv
 */
export class Country extends Model<{
  id?: number;
  c_pais: string;
  descripcion: string;
  formato_codigo_postal: string | null;
  formato_registro_identidad_tributaria: string | null;
  validacion_registro_identidad_tributaria: string | null;
  agrupaciones: string | null;
}> {
  public $resource(): string {
    return 'countries';
  }
}

/**
 * SAT Catalog: Tax Type
 * File: catalogo_impuesto.csv
 */
export class TaxType extends Model<{
  id?: number;
  c_impuesto: string;
  descripcion: string;
  retencion: string;
  traslado: string;
  ambos: string;
}> {
  public $resource(): string {
    return 'tax-types';
  }
}

/**
 * SAT Catalog: Tax Rate or Amount
 * File: catalogo_tasa_o_cuota.csv
 */
export class TaxRate extends Model<{
  id?: number;
  c_rango: string;
  minimo: string;
  valor: string;
  c_impuesto: string;
  c_tipo_factor: string;
  retencion: string;
  traslado: string;
}> {
  public $resource(): string {
    return 'tax-rates';
  }
}

/**
 * SAT Catalog: Relation Types
 * File: catalogo_tipos_de_relacion.csv
 */
export class RelationType extends Model<{
  id?: number;
  c_tipo_de_relacion: string;
  descripcion: string;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'relation-types';
  }
}

/**
 * SAT Catalog: Postal Code
 * File: catalogo_codigo_postal.csv
 * Note: This file doesn't have headers, structure is: codigo_postal, estado, municipio, localidad
 */
export class PostalCode extends Model<{
  id?: number;
  codigo_postal: string;
  estado: string;
  municipio: string;
  localidad: string;
}> {
  public $resource(): string {
    return 'postal-codes';
  }
}

/**
 * SAT Catalog: Payment Form for Services (Complement)
 * File: c_FormaPagoServ.csv
 */
export class PaymentFormService extends Model<{
  id?: number;
  c_periodicidad: string;
  descripcion: string;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'payment-form-services';
  }
}

/**
 * SAT Catalog: Periodicity
 * File: c_Periodicidad.csv
 */
export class Periodicity extends Model<{
  id?: number;
  c_periodicidad: string;
  descripcion: string;
  complemento_que_lo_usa: string;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'periodicities';
  }
}

/**
 * SAT Catalog: Withholding Tax (Retenciones)
 * File: c_Retenciones.csv
 */
export class WithholdingTax extends Model<{
  id?: number;
  c_retenciones: string;
  descripcion: string;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'withholding-taxes';
  }
}

/**
 * SAT Catalog: Service Subtype
 * File: c_SubTipoServ.csv
 */
export class ServiceSubtype extends Model<{
  id?: number;
  c_sub_tipo_serv: string;
  descripcion: string;
  tipo_de_servicio: string;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'service-subtypes';
  }
}

/**
 * SAT Catalog: Tax Rate Amount (Simplified)
 * File: c_TasaCuota.csv
 */
export class TaxRateAmount extends Model<{
  id?: number;
  valor: string;
  impuesto: string;
}> {
  public $resource(): string {
    return 'tax-rate-amounts';
  }
}

/**
 * SAT Catalog: Tax Type (Complement)
 * File: c_TipodeImpuesto.csv
 */
export class TaxTypeComplement extends Model<{
  id?: number;
  c_impuesto: string;
  descripcion: string;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
}> {
  public $resource(): string {
    return 'tax-type-complements';
  }
}

/**
 * SAT Catalog: Service Type
 * File: c_TipoDeServ.csv
 */
export class ServiceType extends Model<{
  id?: number;
  c_tipo_de_serv: string;
  descripcion: string;
  fecha_inicio_vigencia: string;
  fecha_fin_vigencia: string | null;
  isr: string;
  iva: string;
  ejercicio: string;
  fecha_inicio_vigencia_ejercicio: string;
  fecha_fin_vigencia_ejercicio: string | null;
}> {
  public $resource(): string {
    return 'service-types';
  }
}