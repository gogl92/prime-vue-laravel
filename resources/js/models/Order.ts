import { Model } from '@tailflow/laravel-orion/lib/model';

export interface OrderItem {
  type: 'product' | 'service' | 'subscription';
  id: number;
  name: string;
  price: number;
  quantity: number;
  total?: number;
}

export class Order extends Model<
  {
    id?: number;
    order_number: string;
    branch_id: number;
    payment_gateway_id?: number | null;
    customer_email: string;
    customer_name: string;
    customer_phone?: string | null;
    total_amount: number;
    currency?: string;
    status?: 'pending' | 'completed' | 'failed' | 'refunded';
    stripe_payment_intent_id?: string | null;
    stripe_charge_id?: string | null;
    items: OrderItem[];
    customer_notes?: string | null;
    completed_at?: string | null;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
  },
  {
    branch?: any;
    payment_gateway?: any;
  }
> {
  public $resource(): string {
    return 'orders';
  }
}

