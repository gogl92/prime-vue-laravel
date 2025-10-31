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
