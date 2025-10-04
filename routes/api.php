<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Api\AuthController;

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
    Route::put('auth/{auth}', [AuthController::class, 'update'])->name('auth.update');
    Route::patch('auth/{auth}', [AuthController::class, 'update'])->name('auth.update');
    Route::delete('auth/{auth}', [AuthController::class, 'destroy'])->name('auth.destroy');

    // Additional auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });

    // Resource routes
    Orion::resource('invoices', InvoiceController::class);
});
