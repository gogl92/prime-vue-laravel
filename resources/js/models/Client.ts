import { Model } from '@tailflow/laravel-orion/lib/model';

export class Client extends Model<{
  id?: number;
  name: string;
  email: string;
  phone: string;
  address?: string | null;
  city?: string | null;
  state?: string | null;
  zip?: string | null;
  country?: string | null;
  is_supplier?: boolean;
  is_issuer?: boolean;
  created_at?: string;
  updated_at?: string;
}> {
  public $resource(): string {
    return 'clients';
  }
}

