<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;

// Public Pages
Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/merchant/{slug}', [PublicController::class, 'merchantDetail'])->name('merchant.detail');
Route::get('/api/docs', [PublicController::class, 'apiDocs'])->name('api.docs');

// Admin Auth
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin Panel (Protected)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Users
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
    Route::delete('/users/{id}', [AdminController::class, 'usersDestroy'])->name('users.destroy');

    // Merchants
    Route::get('/merchants', [AdminController::class, 'merchantsIndex'])->name('merchants.index');
    Route::get('/merchants/create', [AdminController::class, 'merchantsCreate'])->name('merchants.create');
    Route::post('/merchants', [AdminController::class, 'merchantsStore'])->name('merchants.store');
    Route::delete('/merchants/{id}', [AdminController::class, 'merchantsDestroy'])->name('merchants.destroy');

    // Products / Menu Items
    Route::get('/products', [AdminController::class, 'productsIndex'])->name('products.index');
    Route::get('/products/create', [AdminController::class, 'productsCreate'])->name('products.create');
    Route::post('/products', [AdminController::class, 'productsStore'])->name('products.store');
    Route::delete('/products/{id}', [AdminController::class, 'productsDestroy'])->name('products.destroy');

    // Orders
    Route::get('/orders', [AdminController::class, 'ordersIndex'])->name('orders.index');
    Route::get('/orders/{id}', [AdminController::class, 'ordersShow'])->name('orders.show');
    Route::put('/orders/{id}/status', [AdminController::class, 'ordersUpdateStatus'])->name('orders.status');

    // Promos
    Route::get('/promos', [AdminController::class, 'promosIndex'])->name('promos.index');
    Route::post('/promos', [AdminController::class, 'promosStore'])->name('promos.store');
    Route::delete('/promos/{id}', [AdminController::class, 'promosDestroy'])->name('promos.destroy');
});
