<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SafePhoneNumberCast;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use RichanFongdasen\EloquentBlameable\BlameableTrait;
use Laravel\Cashier\Billable as CashierBillable;
use Lanos\CashierConnect\Billable as ConnectBillable;

class Branch extends Model implements Auditable
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;
    use SoftDeletes;
    use BlameableTrait;
    use AuditableTrait;
    use CashierBillable;
    use ConnectBillable;
    /*
     * public $commission_type = 'fixed';
     * public $commission_rate = 500; // E.G. $5 application fee
     */
    public $commission_type = 'percentage';
    public $commission_rate = 5;

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
    ];

    protected function casts(): array
    {
        return [
            'phone' => SafePhoneNumberCast::class . ':US,MX',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Append custom attributes to model JSON
     *
     * @var array<int, string>
     */
    protected $appends = ['is_stripe_connected'];

    /**
     * Get the is_stripe_connected attribute.
     * A branch is considered connected when it has a Stripe account and can accept payments.
     *
     * @return bool
     */
    public function getIsStripeConnectedAttribute(): bool
    {
        if (!$this->hasStripeAccount()) {
            return false;
        }

        $mapping = $this->stripeAccountMapping;

        return $mapping && $mapping->charges_enabled && $this->hasCompletedOnboarding();
    }

    /**
     * Check if the branch can accept payments via Stripe Connect.
     *
     * @return bool
     */
    public function canAcceptPayments(): bool
    {
        if (!$this->hasStripeAccount()) {
            return false;
        }

        $mapping = $this->stripeAccountMapping;

        return $mapping && $mapping->charges_enabled;
    }
}
