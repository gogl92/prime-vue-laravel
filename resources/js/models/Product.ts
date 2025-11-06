import { Model } from '@tailflow/laravel-orion/lib/model';
import {BelongsToMany} from "@tailflow/laravel-orion/lib/drivers/default/relations/belongsToMany";
import { Invoice } from './Invoice';

export class Product extends Model<{
  id?: number;
  name: string;
  description?: string;
  price: number;
  quantity: number;
  sku: string;
  // CFDI fields
  clave_prod_serv?: string | null;
  clave_unidad?: string | null;
  unidad?: string | null;
  importe?: number | null;
  descuento?: number;
  numero_pedimento?: string | null;
  cuenta_predial?: string | null;
  partes?: Array<any> | null;
  complemento?: Array<any> | null;
  status?: string;
  created_at?: string;
  updated_at?: string;
}, {
    price: number;
 }, {
    invoices: Array<Invoice>,
 }> {
  public invoices(): BelongsToMany<Invoice,{
    quantity: number;
    price: number;
  }> {
    return new BelongsToMany(Invoice, this);
  }

  public $resource(): string {
    return 'products';
  }
}
