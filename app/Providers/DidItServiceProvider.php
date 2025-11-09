<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\FacialRecognitionContract;
use App\Services\DidItService;
use Illuminate\Support\ServiceProvider;

class DidItServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the interface to the concrete implementation
        $this->app->singleton(FacialRecognitionContract::class, function ($app) {
            return new DidItService();
        });

        // Also bind the concrete class for direct usage
        $this->app->singleton(DidItService::class, function ($app) {
            return $app->make(FacialRecognitionContract::class);
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
