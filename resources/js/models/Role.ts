import { Model } from '@tailflow/laravel-orion/lib/model';

export class Role extends Model<{
  id?: number;
  name: string;
  guard_name: string;
  created_at?: string;
  updated_at?: string;
}> {
  public $resource(): string {
    return 'roles';
  }
}
