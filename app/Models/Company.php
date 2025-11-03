<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Propaganistas\LaravelPhone\Casts\SafePhoneNumberCast;
use RichanFongdasen\EloquentBlameable\BlameableTrait;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Company extends Model implements Auditable
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;
    use BlameableTrait;
    use AuditableTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'street_1',
        'street_2',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'email',
        'tax_id',
        'tax_name',
    ];

    protected function casts(): array
    {
        return [
            'phone' => SafePhoneNumberCast::class . ':US,MX',
        ];
    }

    /**
     * Get the user who created the company.
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the company.
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the company.
     * @return BelongsTo<User, $this>
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
