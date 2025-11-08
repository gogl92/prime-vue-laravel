import { Model } from '@tailflow/laravel-orion/lib/model';
import type { Company } from './Company';
import type { Branch } from './Branch';

interface Role {
  id: number;
  name: string;
  guard_name: string;
  created_at?: string;
  updated_at?: string;
}

export class User extends Model<
  {
    id?: number;
    first_name: string;
    last_name: string;
    second_last_name?: string | null;
    username: string;
    phone?: string | null;
    email: string;
    email_verified_at?: string | null;
    password?: string;
    password_confirmation?: string;
    current_company_id?: number | null;
    current_branch_id?: number | null;
    remember_token?: string | null;
    created_by?: number | null;
    updated_by?: number | null;
    deleted_by?: number | null;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
    roles?: string[];
  },
  {
    currentCompany?: Company;
    currentBranch?: Branch;
    roles?: Role[];
  }
> {
  public $resource(): string {
    return 'users';
  }
}
