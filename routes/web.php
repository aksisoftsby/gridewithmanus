<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;

// Public Pages
Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/merchant/{slug}', [PublicController::class, 'merchantDetail'])->name('merchant.detail');
Route::get('/news/{slug}', [PublicController::class, 'newsDetail'])->name('news.detail');


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
    Route::get('/products/{id}/edit', [AdminController::class, 'productsEdit'])->name('products.edit');
    Route::put('/products/{id}', [AdminController::class, 'productsUpdate'])->name('products.update');

    // Orders
    Route::get('/orders', [AdminController::class, 'ordersIndex'])->name('orders.index');
    Route::get('/orders/{id}', [AdminController::class, 'ordersShow'])->name('orders.show');
    Route::put('/orders/{id}/status', [AdminController::class, 'ordersUpdateStatus'])->name('orders.status');

    // Promos
    Route::get('/promos', [AdminController::class, 'promosIndex'])->name('promos.index');
    Route::get('/promos/create', [AdminController::class, 'promosCreate'])->name('promos.create');
    Route::post('/promos', [AdminController::class, 'promosStore'])->name('promos.store');
    Route::delete('/promos/{id}', [AdminController::class, 'promosDestroy'])->name('promos.destroy');
    Route::get('/promos/{id}/edit', [AdminController::class, 'promosEdit'])->name('promos.edit');
    Route::put('/promos/{id}', [AdminController::class, 'promosUpdate'])->name('promos.update');

    // Drivers
    Route::get('/drivers', [AdminController::class, 'driversIndex'])->name('drivers.index');
    Route::get('/drivers/create', [AdminController::class, 'driversCreate'])->name('drivers.create');
    Route::post('/drivers', [AdminController::class, 'driversStore'])->name('drivers.store');
    Route::put('/drivers/{id}/status', [AdminController::class, 'driversUpdateStatus'])->name('drivers.status');
    Route::delete('/drivers/{id}', [AdminController::class, 'driversDestroy'])->name('drivers.destroy');

    // Chat Sessions
    Route::get('/chats', [AdminController::class, 'chatsIndex'])->name('chats.index');
    Route::delete('/chats/{id}', [AdminController::class, 'chatsDestroy'])->name('chats.destroy');
    Route::post('/chats/flush', [AdminController::class, 'chatsFlush'])->name('chats.flush');

    // News
    Route::get('/news', [AdminController::class, 'newsIndex'])->name('news.index');
    Route::get('/news/create', [AdminController::class, 'newsCreate'])->name('news.create');
    Route::post('/news', [AdminController::class, 'newsStore'])->name('news.store');
    Route::get('/news/{id}/edit', [AdminController::class, 'newsEdit'])->name('news.edit');
    Route::put('/news/{id}', [AdminController::class, 'newsUpdate'])->name('news.update');
    Route::delete('/news/{id}', [AdminController::class, 'newsDestroy'])->name('news.destroy');

    // Testimonials
    Route::get('/testimonials', [AdminController::class, 'testimonialsIndex'])->name('testimonials.index');
    Route::get('/testimonials/create', [AdminController::class, 'testimonialsCreate'])->name('testimonials.create');
    Route::post('/testimonials', [AdminController::class, 'testimonialsStore'])->name('testimonials.store');
    Route::get('/testimonials/{id}/edit', [AdminController::class, 'testimonialsEdit'])->name('testimonials.edit');
    Route::put('/testimonials/{id}', [AdminController::class, 'testimonialsUpdate'])->name('testimonials.update');
    Route::delete('/testimonials/{id}', [AdminController::class, 'testimonialsDestroy'])->name('testimonials.destroy');

    // Merchant edit (full editability)
    Route::get('/merchants/{id}/edit', [AdminController::class, 'merchantsEdit'])->name('merchants.edit');
    Route::put('/merchants/{id}', [AdminController::class, 'merchantsUpdate'])->name('merchants.update');

    // User edit (full editability)
    Route::get('/users/{id}/edit', [AdminController::class, 'usersEdit'])->name('users.edit');
    Route::put('/users/{id}', [AdminController::class, 'usersUpdate'])->name('users.update');

    // Driver edit (full editability)
    Route::get('/drivers/{id}/edit', [AdminController::class, 'driversEdit'])->name('drivers.edit');
    Route::put('/drivers/{id}', [AdminController::class, 'driversUpdate'])->name('drivers.update');

    // Settings (GitHub APK artifact download links)
    Route::get('/settings', [AdminController::class, 'settingsIndex'])->name('settings.index');
    Route::post('/settings', [AdminController::class, 'settingsUpdate'])->name('settings.update');
    Route::post('/settings/refresh-links', [AdminController::class, 'settingsRefreshLinks'])->name('settings.refresh-links');

    // API documentation (admin-only, removed from public)
    Route::get('/api/docs', [AdminController::class, 'apiDocs'])->name('api.docs');
});
