<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    // Note: Password update is handled by Fortify at PUT /user/password
    // Keeping this route commented to avoid conflicts
    // Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::get('settings/stripe-connect', function () {
        return Inertia::render('settings/BranchStripeConnect');
    })->name('stripe.connect');

    Route::get('settings/branch-invoicing', function () {
        return Inertia::render('settings/BranchInvoiceSettings');
    })->name('branch.invoicing');

    Route::get('settings/services', function () {
        return Inertia::render('settings/Services');
    })->name('settings.services');

    Route::get('settings/services/create', function () {
        return Inertia::render('settings/ServiceForm');
    })->name('settings.services.create');

    Route::get('settings/services/{id}/edit', function ($id) {
        return Inertia::render('settings/ServiceForm', ['id' => $id]);
    })->name('settings.services.edit');

    Route::get('settings/payment-gateways', function () {
        return Inertia::render('settings/PaymentGatewaySettings');
    })->name('settings.payment-gateways');

    Route::get('settings/stripe/return', function () {
        return Inertia::render('settings/StripeReturn');
    })->name('stripe.return');

    Route::get('settings/stripe/refresh', function () {
        return Inertia::render('settings/StripeRefresh');
    })->name('stripe.refresh');
});
