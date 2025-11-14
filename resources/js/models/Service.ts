import { Model } from '@tailflow/laravel-orion/lib/model';

export class Service extends Model<
  {
    id?: number;
    branch_id: number;
    name: string;
    description?: string | null;
    price: number;
    duration?: number | null;
    sku?: string | null;
    is_active?: boolean;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
  },
  {
    branch?: any; // Can be typed as Branch if needed
  }
> {
  public $resource(): string {
    return 'services';
  }
}

