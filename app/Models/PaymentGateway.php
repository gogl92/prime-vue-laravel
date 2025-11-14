<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PaymentGatewayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use RichanFongdasen\EloquentBlameable\BlameableTrait;

class PaymentGateway extends Model implements Auditable
{
    use AuditableTrait;
    use BlameableTrait;
    /** @use HasFactory<PaymentGatewayFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'is_enabled',
        'slug',
        'business_name',
        'logo_url',
        'primary_color',
        'secondary_color',
        'available_product_ids',
        'available_service_ids',
        'available_subscription_ids',
        'terms_and_conditions',
        'success_message',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'available_product_ids' => 'array',
            'available_service_ids' => 'array',
            'available_subscription_ids' => 'array',
        ];
    }

    /**
     * Get the branch that owns the payment gateway.
     *
     * @return BelongsTo<Branch, PaymentGateway>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Generate a unique slug for the payment gateway.
     */
    public static function generateUniqueSlug(?string $baseName = null): string
    {
        $slug = $baseName ? Str::slug($baseName) : Str::random(8);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PaymentGateway $gateway) {
            if (empty($gateway->slug)) {
                $gateway->slug = static::generateUniqueSlug($gateway->business_name ?? $gateway->branch?->name);
            }
        });
    }
}
