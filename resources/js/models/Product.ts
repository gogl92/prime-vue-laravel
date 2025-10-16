import { Model } from '@tailflow/laravel-orion/lib/model';

export class Product extends Model<{
  id?: number;
  name: string;
  description?: string;
  price: number;
  quantity: number;
  sku: string;
  created_at?: string;
  updated_at?: string;
  pivot?: {
    quantity: number;
    price: number;
    created_at?: string;
    updated_at?: string;
  };
}> {
  public $resource(): string {
    return 'products';
  }
}
