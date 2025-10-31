<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/invoices', function () {
    return Inertia::render('InvoicesExample');
})->middleware(['auth', 'verified'])->name('invoices.example');

Route::get('/invoices/create', function () {
    return Inertia::render('CreateInvoice');
})->middleware(['auth', 'verified'])->name('invoices.create');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
