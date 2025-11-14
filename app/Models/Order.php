<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Order extends Model implements Auditable
{
    use AuditableTrait;
    /** @use HasFactory<OrderFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'branch_id',
        'payment_gateway_id',
        'customer_email',
        'customer_name',
        'customer_phone',
        'total_amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'items',
        'customer_notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'items' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the branch that owns the order.
     *
     * @return BelongsTo<Branch, Order>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the payment gateway used for this order.
     *
     * @return BelongsTo<PaymentGateway, Order>
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(substr(md5((string) time() . rand()), 0, 8));
        } while (static::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }
}
