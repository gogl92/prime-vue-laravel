import { Model } from '@tailflow/laravel-orion/lib/model';
import { Product } from './Product';
import { BelongsToMany } from '@tailflow/laravel-orion/lib/drivers/default/relations/belongsToMany';
export class Invoice extends Model<{
  id?: number;
  uuid?: string | null;
  client_id: number;
  issuer_id?: number | null;
  supplier_id?: number | null;
  cfdi_type?: string;
  order_number?: string | null;
  date?: string | null;
  payment_form?: string | null;
  send_email?: boolean;
  payment_method?: string | null;
  cfdi_use?: string | null;
  series?: string | null;
  exchange_rate?: number;
  currency?: string;
  comments?: string | null;
  // CFDI financial fields
  import?: number | null;
  import_usd?: number | null;
  sub_total?: number | null;
  retention_tax?: number;
  iva_tax?: number;
  paid?: number;
  // CFDI sender/receiver info
  sender_name?: string | null;
  sender_rfc?: string | null;
  receipt_rfc?: string | null;
  receipt_type?: string | null;
  // CFDI complement
  complement_id?: string | null;
  complement_date?: string | null;
  // Files
  pdf?: string | null;
  xml_path?: string | null;
  cfdi_json?: string | null;
  // Other
  expenses_type_id?: number | null;
  status?: string;
  token?: string | null;
  created_at?: string;
  updated_at?: string;
  client?: {
    id: number;
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
  };
  issuer?: {
    id: number;
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
  };
  supplier?: {
    id: number;
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
  };
  products?: Array<{
    id: number;
    name: string;
    description?: string;
    price: number;
    quantity: number;
    sku: string;
    pivot: {
      quantity: number;
      price: number;
    };
  }>;
  payments?: Array<{
    id: number;
    invoice_id: number;
    amount: number;
    payment_method: string;
    transaction_id?: string | null;
    status: string;
    notes?: string | null;
    paid_at?: string | null;
    created_at?: string;
    updated_at?: string;
  }>;
}> {
  public products(): BelongsToMany<Product,{
    quantity: number;
    price: number;
  }> {
    return new BelongsToMany(Product, this);
  }
  public $resource(): string {
    return 'invoices';
  }
}
