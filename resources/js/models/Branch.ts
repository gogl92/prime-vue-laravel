import { Model } from '@tailflow/laravel-orion/lib/model';

export interface StripeAccountMapping {
  model: string;
  model_id: number;
  model_uuid?: string | null;
  stripe_account_id: string;
  future_requirements?: Record<string, any> | null;
  charges_enabled: boolean;
  first_onboarding_done: boolean;
  requirements?: Record<string, any> | null;
  type: string;
}

export class Branch extends Model<
  {
    id?: number;
    name: string;
    code: string;
    email: string;
    phone: string;
    company_id?: number | null;
    address?: string | null;
    city?: string | null;
    state?: string | null;
    zip?: string | null;
    country?: string | null;
    is_active?: boolean;
    description?: string | null;
    is_stripe_connected?: boolean;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
    stripe_account_mapping?: StripeAccountMapping | null;
  },
  {
    stripe_account_mapping?: StripeAccountMapping;
  }
> {
  public $resource(): string {
    return 'branches';
  }
}
