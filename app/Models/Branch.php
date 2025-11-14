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
use Rawilk\Settings\Models\HasSettings;
use RichanFongdasen\EloquentBlameable\BlameableTrait;

/**
 * @property-read StripeAccountMapping|null $stripeAccountMapping
 *
 * @method string|null accountDashboardUrl(array $options = [])
 */
class Branch extends Model implements Auditable, StripeAccount
{
    use AuditableTrait;
    use BlameableTrait;
    use CashierBillable;
    use ConnectBillable;
    /** @use HasFactory<BranchFactory> */
    use HasFactory;
    use HasSettings;
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

    /**
     * Get the configured invoicing service for this branch
     *
     * @return \App\Contracts\MexicanInvoicingContract
     */
    public function getInvoicingService(): \App\Contracts\MexicanInvoicingContract
    {
        $provider = $this->settings()->get('invoicing_provider', 'facturacom');

        return match ($provider) {
            'facturapi' => $this->getFacturapiService(),
            'facturacom' => $this->getFacturacomService(),
            default => $this->getFacturacomService(),
        };
    }

    /**
     * Get Facturacom service instance with branch-specific credentials
     *
     * @return \App\Services\FacturacomService
     */
    public function getFacturacomService(): \App\Services\FacturacomService
    {
        $apiUrl = $this->settings()->get('facturacom_api_url');
        $apiKey = $this->settings()->get('facturacom_api_key');
        $secretKey = $this->settings()->get('facturacom_secret_key');
        $pluginKey = $this->settings()->get('facturacom_plugin_key');

        // If branch has custom credentials, use them
        if ($apiUrl || $apiKey || $secretKey || $pluginKey) {
            return new \App\Services\FacturacomService(
                apiUrl: $apiUrl,
                apiKey: $apiKey,
                secretKey: $secretKey,
                pluginKey: $pluginKey
            );
        }

        // Otherwise, use default config credentials
        return new \App\Services\FacturacomService();
    }

    /**
     * Get Facturapi service instance with branch-specific credentials
     *
     * @return \App\Services\FacturapiService
     */
    public function getFacturapiService(): \App\Services\FacturapiService
    {
        $apiUrl = $this->settings()->get('facturapi_api_url');
        $apiKey = $this->settings()->get('facturapi_api_key');

        // If branch has custom credentials, use them
        if ($apiUrl || $apiKey) {
            return new \App\Services\FacturapiService(
                apiUrl: $apiUrl,
                apiKey: $apiKey
            );
        }

        // Otherwise, use default config credentials
        return new \App\Services\FacturapiService();
    }

    /**
     * Get the services for the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Service>
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the payment gateway for the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<PaymentGateway>
     */
    public function paymentGateway()
    {
        return $this->hasOne(PaymentGateway::class);
    }

    /**
     * Get the orders for the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Order>
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
