<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lanos\CashierConnect\ConnectCustomer;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Propaganistas\LaravelPhone\Casts\SafePhoneNumberCast;
use RichanFongdasen\EloquentBlameable\BlameableTrait;

class Customer extends Model implements Auditable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;
    use BlameableTrait;
    use AuditableTrait;
    use SoftDeletes;
    use ConnectCustomer;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'street_1',
        'street_2',
        'city',
        'state',
        'zip',
        'country',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'phone' => SafePhoneNumberCast::class . ':US,MX',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user who created the customer.
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the customer.
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the customer.
     * @return BelongsTo<User, $this>
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

