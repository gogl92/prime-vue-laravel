<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\MexicanInvoicingContract;
use App\Services\FacturacomService;
use App\Services\FacturapiService;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class MexicanInvoicingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the interface to the concrete implementation based on the configured provider
        $this->app->singleton(MexicanInvoicingContract::class, function ($app) {
            $provider = config('invoicing.provider', 'facturacom');

            return match ($provider) {
                'facturapi' => new FacturapiService(),
                'facturacom' => new FacturacomService(),
                default => throw new InvalidArgumentException("Unsupported invoicing provider: $provider"),
            };
        });

        // Also bind each concrete class for direct usage
        $this->app->singleton(FacturacomService::class, function ($app) {
            return new FacturacomService();
        });

        $this->app->singleton(FacturapiService::class, function ($app) {
            return new FacturapiService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
