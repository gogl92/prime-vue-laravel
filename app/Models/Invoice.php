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
        'supplier_id',
        'cfdi_type',
        'order_number',
        'date',
        'payment_form',
        'send_email',
        'payment_method',
        'cfdi_use',
        'series',
        'exchange_rate',
        'currency',
        'comments',
        // CFDI fields
        'uuid',
        'import',
        'import_usd',
        'sub_total',
        'retention_tax',
        'iva_tax',
        'paid',
        'sender_name',
        'sender_rfc',
        'receipt_rfc',
        'receipt_type',
        'complement_id',
        'complement_date',
        'pdf',
        'xml_path',
        'cfdi_json',
        'expenses_type_id',
        'status',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'complement_date' => 'date',
            'send_email' => 'boolean',
            'exchange_rate' => 'decimal:4',
            'import' => 'decimal:2',
            'import_usd' => 'decimal:2',
            'sub_total' => 'decimal:2',
            'retention_tax' => 'decimal:2',
            'iva_tax' => 'decimal:2',
            'paid' => 'decimal:2',
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
     * Get the supplier for the invoice.
     * @return BelongsTo<Client, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'supplier_id');
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
