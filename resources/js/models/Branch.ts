import { Model } from '@tailflow/laravel-orion/lib/model';

export class Branch extends Model<{
  id?: number;
  name: string;
  code: string;
  email: string;
  phone: string;
  address?: string | null;
  city?: string | null;
  state?: string | null;
  zip?: string | null;
  country?: string | null;
  is_active?: boolean;
  description?: string | null;
  stripe_id?: string | null;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
}> {
  public $resource(): string {
    return 'branches';
  }
}
