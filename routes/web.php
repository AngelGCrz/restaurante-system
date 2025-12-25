<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome');
})->name('home');

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\CashController;

Route::middleware(['auth'])->group(function () {

    // Rutas para Admin
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('products', ProductController::class);
        Route::resource('tables', TableController::class);
        Route::get('reports', [ProductController::class, 'reports'])->name('reports');
    });

    // Rutas para Cajero
    Route::middleware(['role:cajero'])->group(function () {
        Route::resource('orders', OrderController::class)->only(['index', 'show']);
        Route::get('cash', [CashController::class, 'index'])->name('cash.index');
        Route::post('cash/open', [CashController::class, 'open'])->name('cash.open');
        Route::post('cash/close', [CashController::class, 'close'])->name('cash.close');
    });

    // Rutas para Cocina
    Route::middleware(['role:cocina'])->group(function () {
        Route::get('kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
    });

    // Rutas para Mozo
    Route::middleware(['role:mozo'])->prefix('waiter')->group(function () {
        Route::get('orders/create', [OrderController::class, 'create'])->name('mozo.orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('mozo.orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('mozo.orders.show');
    });
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
