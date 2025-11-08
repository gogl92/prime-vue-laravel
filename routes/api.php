<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InvoiceProductsController;
use App\Http\Controllers\InvoicePaymentsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StripeController;
// SAT Catalog Controllers
use App\Http\Controllers\SAT\ProductKeyController;
use App\Http\Controllers\SAT\UnitKeyController;
use App\Http\Controllers\SAT\PaymentFormController;
use App\Http\Controllers\SAT\PaymentMethodController;
use App\Http\Controllers\SAT\CFDIUseController;
use App\Http\Controllers\SAT\CurrencyController;
use App\Http\Controllers\SAT\TaxRegimeController;
use App\Http\Controllers\SAT\CountryController;
use App\Http\Controllers\SAT\TaxTypeController;
use App\Http\Controllers\SAT\TaxRateController;
use App\Http\Controllers\SAT\RelationTypeController;
use App\Http\Controllers\SAT\PostalCodeController;
use App\Http\Controllers\SAT\PaymentFormServiceController;
use App\Http\Controllers\SAT\PeriodicityController;
use App\Http\Controllers\SAT\WithholdingTaxController;
use App\Http\Controllers\SAT\ServiceSubtypeController;
use App\Http\Controllers\SAT\TaxRateAmountController;
use App\Http\Controllers\SAT\TaxTypeComplementController;
use App\Http\Controllers\SAT\ServiceTypeController;

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::get('create', [AuthController::class, 'create']);
    Route::post('/', [AuthController::class, 'store']); // Register
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Authentication resource routes
    Route::get('auth', [AuthController::class, 'index'])->name('auth.index');
    Route::get('auth/{auth}', [AuthController::class, 'show'])->name('auth.show');
    Route::get('auth/{auth}/edit', [AuthController::class, 'edit'])->name('auth.edit');
    Route::match(['put', 'patch'], 'auth/{auth}', [AuthController::class, 'update'])->name('auth.update');
    Route::delete('auth/{auth}', [AuthController::class, 'destroy'])->name('auth.destroy');

    // Additional auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });

    // Stripe Connect onboarding routes
    Route::prefix('stripe/branches/{id}')->group(function () {
        Route::post('onboarding', [StripeController::class, 'generateOnboardingUrl']);
        Route::get('status', [StripeController::class, 'getOnboardingStatus']);
        Route::get('dashboard', [StripeController::class, 'getDashboardUrl']);
        Route::post('reset', [StripeController::class, 'resetAccount']);
    });

    // Resource routes
    Orion::resource('users', UserController::class);
    Orion::resource('roles', RoleController::class)->only(['index', 'search']);
    Orion::resource('companies', CompanyController::class);
    Orion::resource('branches', BranchController::class);
    Orion::resource('clients', ClientController::class);
    Orion::resource('customers', CustomerController::class);
    Orion::resource('invoices', InvoiceController::class);
    Orion::resource('products', ProductController::class);
    Orion::resource('payments', PaymentController::class);
    Orion::belongsToManyResource('invoices', 'products', InvoiceProductsController::class);
    Orion::hasManyResource('invoices', 'payments', InvoicePaymentsController::class);

    // SAT Catalog Resources (Read-only: index and search only)
    Orion::resource('product-keys', ProductKeyController::class)->only(['index', 'search']);
    Orion::resource('unit-keys', UnitKeyController::class)->only(['index', 'search']);
    Orion::resource('payment-forms', PaymentFormController::class)->only(['index', 'search']);
    Orion::resource('payment-methods', PaymentMethodController::class)->only(['index', 'search']);
    Orion::resource('cfdi-uses', CFDIUseController::class)->only(['index', 'search']);
    Orion::resource('currencies', CurrencyController::class)->only(['index', 'search']);
    Orion::resource('tax-regimes', TaxRegimeController::class)->only(['index', 'search']);
    Orion::resource('countries', CountryController::class)->only(['index', 'search']);
    Orion::resource('tax-types', TaxTypeController::class)->only(['index', 'search']);
    Orion::resource('tax-rates', TaxRateController::class)->only(['index', 'search']);
    Orion::resource('relation-types', RelationTypeController::class)->only(['index', 'search']);
    Orion::resource('postal-codes', PostalCodeController::class)->only(['index', 'search']);
    Orion::resource('payment-form-services', PaymentFormServiceController::class)->only(['index', 'search']);
    Orion::resource('periodicities', PeriodicityController::class)->only(['index', 'search']);
    Orion::resource('withholding-taxes', WithholdingTaxController::class)->only(['index', 'search']);
    Orion::resource('service-subtypes', ServiceSubtypeController::class)->only(['index', 'search']);
    Orion::resource('tax-rate-amounts', TaxRateAmountController::class)->only(['index', 'search']);
    Orion::resource('tax-type-complements', TaxTypeComplementController::class)->only(['index', 'search']);
    Orion::resource('service-types', ServiceTypeController::class)->only(['index', 'search']);
});
