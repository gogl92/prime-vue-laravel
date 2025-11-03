import { Model } from '@tailflow/laravel-orion/lib/model';

export class Company extends Model<{
  id?: number;
  name: string;
  street_1: string;
  street_2: string;
  city: string;
  state: string;
  zip: string;
  country: string;
  phone: string;
  email: string;
  tax_id: string;
  tax_name: string;
  created_by?: number | null;
  updated_by?: number | null;
  deleted_by?: number | null;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
}> {
  public $resource(): string {
    return 'companies';
  }
}
