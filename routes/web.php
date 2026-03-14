<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TableSettingsController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\OrderController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;


Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {

    // Rutas para Admin
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('products', ProductController::class);
        Route::get('settings/stock', [\App\Http\Controllers\Admin\StockSettingsController::class, 'edit'])->name('settings.stock.edit');
        Route::put('settings/stock', [\App\Http\Controllers\Admin\StockSettingsController::class, 'update'])->name('settings.stock.update');
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('tables', [TableSettingsController::class, 'edit'])->name('tables.edit');
        Route::put('tables', [TableSettingsController::class, 'update'])->name('tables.update');
        Route::get('tables/release', [\App\Http\Controllers\Admin\ReleaseTableController::class, 'index'])->name('tables.release');
        Route::post('tables/release', [\App\Http\Controllers\Admin\ReleaseTableController::class, 'release'])->name('tables.release.confirm');
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('tags', TagController::class)->except(['show']);
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('ventas', [ReportController::class, 'sales'])->name('sales');
            Route::get('ventas-detalladas', [ReportController::class, 'salesdetallada'])->name('salesdetallada');
            Route::get('caja', [ReportController::class, 'cash'])->name('cash');
            Route::get('inventario', [ReportController::class, 'inventory'])->name('inventory');
            Route::get('clientes', [ReportController::class, 'customers'])->name('customers');
            Route::get('mesas', [ReportController::class, 'tables'])->name('tables');
            Route::get('cocina', [ReportController::class, 'kitchen'])->name('kitchen');
            Route::get('ganancias', [ReportController::class, 'profit'])->name('profit');
        });
    });
    Route::get('/test-print-api', function () {
    return response()->json(['ok' => true]);
});
Route::get('/ping', function () {
    return 'pong';
});



    // Rutas para Cajero
    Route::middleware(['role:cajero'])->group(function () {
        // ⚠️ Rutas específicas ANTES del resource para evitar conflicto con {order}
        Route::get('/orders/poll-caja', [OrderController::class, 'pollCaja'])->name('orders.poll-caja');
        Route::get('/orders/payments',  [OrderController::class, 'payments'])->name('orders.payments');
        Route::post('/orders/pay-table', [OrderController::class, 'payTable'])->name('orders.pay-table');
        Route::resource('orders', OrderController::class)->only(['index', 'show']);
        Route::post('orders/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('caja/dashboard', [\App\Http\Controllers\CajaController::class, 'dashboard'])->name('caja.dashboard');
        Route::get('cash', [CashController::class, 'index'])->name('cash.index');
        Route::post('cash/open', [CashController::class, 'open'])->name('cash.open');
        Route::post('cash/close', [CashController::class, 'close'])->name('cash.close');
    });

    // Rutas de cocina accesibles tambien por cajero (dashboard unificado)
    Route::middleware(['role:cocina|cajero'])->group(function () {
        Route::get('kitchen/poll',                  [KitchenController::class, 'poll'])->name('kitchen.poll');
        Route::post('kitchen/{order}/print',        [KitchenController::class, 'printOrder'])->name('kitchen.print');
        Route::post('kitchen/{order}/mark-printed', [KitchenController::class, 'markPrinted'])->name('kitchen.markPrinted');
    });

    // Rutas exclusivas del rol cocina
    Route::middleware(['role:cocina'])->group(function () {
        Route::get('kitchen',                   [KitchenController::class, 'index'])->name('kitchen.index');
        Route::post('kitchen/{order}/prepare',  [KitchenController::class, 'prepare'])->name('kitchen.prepare');
        Route::post('kitchen/{order}/ready',    [KitchenController::class, 'ready'])->name('kitchen.ready');
        Route::get('kitchen/{order}',           [KitchenController::class, 'show'])->name('kitchen.show');
    });

    


    // Rutas para Mozo
    Route::middleware(['role:mozo'])->prefix('waiter')->group(function () {
        Route::get('orders', [OrderController::class, 'mozoIndex'])->name('mozo.orders.index');
        Route::get('orders/create', [OrderController::class, 'create'])->name('mozo.orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('mozo.orders.store');
        Route::get('orders/pending-by-table/{table}', [OrderController::class, 'pendingByTable'])->name('mozo.orders.pending-by-table');
        Route::get('orders/{order}/add-items', [OrderController::class, 'addItemsForm'])->name('mozo.orders.add-items');
        Route::post('orders/{order}/add-items', [OrderController::class, 'addItemsStore'])->name('mozo.orders.add-items.store');
        // Cambiar mesa: formulario y acción para trasladar un pedido a otra mesa libre
        Route::get('orders/{order}/change-table', [OrderController::class, 'changeTableForm'])->name('mozo.orders.change-table');
        Route::post('orders/{order}/change-table', [OrderController::class, 'changeTableStore'])->name('mozo.orders.change-table.update');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('mozo.orders.show');
        Route::get('tables/select', [OrderController::class, 'selectTables'])->name('mozo.tables.select');        
        
        Route::get('/waiter/orders', [OrderController::class, 'mozoIndex'])->name('mozo.orders.index');


    });

        


});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::post('/orders/add-product', [OrderController::class, 'addProduct'])->name('order.addProduct');
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