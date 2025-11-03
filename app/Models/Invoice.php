<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use RichanFongdasen\EloquentBlameable\BlameableTrait;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Invoice extends Model implements Auditable
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;
    use BlameableTrait;
    use AuditableTrait;

    protected $fillable = [
        'client_id',
        'issuer_id',
        'cfdi_type',
        'order_number',
        'invoice_date',
        'payment_form',
        'send_email',
        'payment_method',
        'cfdi_use',
        'series',
        'exchange_rate',
        'currency',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'send_email' => 'boolean',
            'exchange_rate' => 'decimal:4',
        ];
    }

    /**
     * Get the client (customer) for the invoice.
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the issuer (company) for the invoice.
     * @return BelongsTo<Client, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'issuer_id');
    }

    /**
     * Get the products that belong to the invoice.
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['quantity', 'price'])
            ->withTimestamps();
    }

    /**
     * Get the payments for the invoice.
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
