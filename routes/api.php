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
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StripeController;

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
    Orion::resource('companies', CompanyController::class);
    Orion::resource('branches', BranchController::class);
    Orion::resource('clients', ClientController::class);
    Orion::resource('customers', CustomerController::class);
    Orion::resource('invoices', InvoiceController::class);
    Orion::resource('products', ProductController::class);
    Orion::resource('payments', PaymentController::class);
    Orion::belongsToManyResource('invoices', 'products', InvoiceProductsController::class);
    Orion::hasManyResource('invoices', 'payments', InvoicePaymentsController::class);
});
