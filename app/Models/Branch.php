<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SafePhoneNumberCast;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lanos\CashierConnect\Billable as ConnectBillable;
use Lanos\CashierConnect\Contracts\StripeAccount;
use Lanos\CashierConnect\Models\StripeAccountMapping;
use Laravel\Cashier\Billable as CashierBillable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use RichanFongdasen\EloquentBlameable\BlameableTrait;

/**
 * @property-read StripeAccountMapping|null $stripeAccountMapping
 *
 * @method string stripeAccountDashboardUrl()
 */
class Branch extends Model implements Auditable, StripeAccount
{
    use AuditableTrait;
    use BlameableTrait;
    use CashierBillable;
    use ConnectBillable;
    /** @use HasFactory<BranchFactory> */
    use HasFactory;
    use SoftDeletes;

    /*
     * public string $commission_type = 'fixed';
     * public int $commission_rate = 500; // E.G. $5 application fee
     */
    public string $commission_type = 'percentage';

    public int $commission_rate = 5;

    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'is_active',
        'description',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'phone' => SafePhoneNumberCast::class.':US,MX',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Append custom attributes to model JSON
     *
     * @var list<string>
     */
    protected $appends = ['is_stripe_connected'];

    /**
     * Get the is_stripe_connected attribute.
     * A branch is considered connected when it has a Stripe account and can accept payments.
     */
    public function getIsStripeConnectedAttribute(): bool
    {
        if (! $this->hasStripeAccount()) {
            return false;
        }

        $mapping = $this->stripeAccountMapping;

        return $mapping && $mapping->charges_enabled && $this->hasCompletedOnboarding();
    }

    /**
     * Check if the branch can accept payments via Stripe Connect.
     */
    public function canAcceptPayments(): bool
    {
        if (! $this->hasStripeAccount()) {
            return false;
        }

        $mapping = $this->stripeAccountMapping;

        return $mapping && $mapping->charges_enabled;
    }

    /**
     * Get the company that owns the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Company, Branch>
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
