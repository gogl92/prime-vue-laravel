import { Model } from '@tailflow/laravel-orion/lib/model';
import { Branch } from './Branch';

export class PaymentGateway extends Model<
  {
    id?: number;
    branch_id: number;
    is_enabled?: boolean;
    slug: string;
    business_name?: string | null;
    logo_url?: string | null;
    primary_color?: string;
    secondary_color?: string;
    available_product_ids?: number[] | null;
    available_service_ids?: number[] | null;
    available_subscription_ids?: number[] | null;
    terms_and_conditions?: string | null;
    success_message?: string | null;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
  },
  {
    branch?: Branch;
  }
> {
  public $resource(): string {
    return 'payment-gateways';
  }
}

