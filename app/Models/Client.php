<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SafePhoneNumberCast;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use RichanFongdasen\EloquentBlameable\BlameableTrait;

class Client extends Model implements Auditable
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;
    use BlameableTrait;
    use AuditableTrait;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'is_supplier',
        'is_issuer',
    ];

    protected function casts(): array
    {
        return [
            'phone' => SafePhoneNumberCast::class . ':US,MX',
            'is_supplier' => 'boolean',
            'is_issuer' => 'boolean',
        ];
    }

    /**
     * Get the invoices where this client is the customer.
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    /**
     * Get the invoices where this client is the issuer.
     * @return HasMany<Invoice, $this>
     */
    public function issuedInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'issuer_id');
    }
}
