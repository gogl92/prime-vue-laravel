import { Model } from '@tailflow/laravel-orion/lib/model';

export class Invoice extends Model<{
    id?: number
    name: string
    email: string
    phone: string
    address: string
    city: string
    state: string
    zip: string
    country: string
    created_at?: string
    updated_at?: string
    products?: Array<{
        id: number
        name: string
        description?: string
        price: number
        quantity: number
        sku: string
        pivot: {
            quantity: number
            price: number
        }
    }>
}> {
    public $resource(): string {
        return 'invoices';
    }
}
