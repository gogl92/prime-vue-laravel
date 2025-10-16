import { Model } from '@tailflow/laravel-orion/lib/model';

export class Payment extends Model<{
  id?: number;
  invoice_id: number;
  amount: number;
  payment_method: string;
  transaction_id?: string | null;
  status: string;
  notes?: string | null;
  paid_at?: string | null;
  created_at?: string;
  updated_at?: string;
}> {
  public $resource(): string {
    return 'payments';
  }
}
