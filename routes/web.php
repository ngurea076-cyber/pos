<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\NotificationFeedController;
use App\Livewire\Dashboard;
use App\Livewire\Expenses;
use App\Livewire\Notifications;
use App\Livewire\Inventory;
use App\Livewire\Orders;
use App\Livewire\PointOfSale;
use App\Livewire\Products;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/pos', PointOfSale::class)->name('pos');
    Route::get('/orders/{order?}', Orders::class)->name('orders');
    Route::get('/orders/{order}/receipt.pdf', [ReceiptController::class, 'download'])->name('orders.receipt');
    Route::get('/expenses', Expenses::class)->name('expenses');
    Route::get('/notifications', Notifications::class)->name('notifications');
    Route::get('/notifications/feed', NotificationFeedController::class)->name('notifications.feed');
    Route::get('/products', Products::class)->name('products');
    Route::redirect('/inventory', '/inventory/actions')->name('inventory');
    Route::get('/inventory/{section}', Inventory::class)->whereIn('section', ['actions', 'records'])->name('inventory.section');
    Route::get('/inventory/serial/{serial}', \App\Livewire\SerialHistory::class)->name('inventory.serial-history');
    Route::middleware('admin')->group(function () {
        Route::get('/finance', \App\Livewire\Finance::class)->name('finance');
        Route::get('/finance/status/{serial}', \App\Livewire\FinanceSerialStatus::class)->name('finance.status');
        Route::get('/reports', \App\Livewire\Reports::class)->name('reports');
        Route::get('/suppliers', \App\Livewire\Suppliers::class)->name('suppliers');
        Route::get('/resellers', \App\Livewire\Resellers::class)->name('resellers');
    });
});
