<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\KotaAuthController;
use App\Http\Controllers\KotaController;

// Public Pages
Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/proposal', [PublicController::class, 'proposal'])->name('proposal');
Route::get('/merchant/{slug}', [PublicController::class, 'merchantDetail'])->name('merchant.detail');
Route::get('/news/{slug}', [PublicController::class, 'newsDetail'])->name('news.detail');
Route::get('/iklan', [PublicController::class, 'iklanIndex'])->name('iklan.index');
Route::get('/iklan/{id}', [PublicController::class, 'iklanDetail'])->name('iklan.detail');


// Admin Auth
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Iklan Baris WebView (tanpa header/footer, untuk webview aplikasi)
Route::get('/iklan-webview', [\App\Http\Controllers\IklanWebviewController::class, 'index'])->name('iklanwebview.index');
Route::get('/iklan-webview/{id}', [\App\Http\Controllers\IklanWebviewController::class, 'detail'])->name('iklanwebview.detail')->where('id', '[0-9]+');
Route::middleware(['auth'])->group(function () {
    Route::get('/iklan-webview/posting', [\App\Http\Controllers\IklanWebviewController::class, 'create'])->name('iklanwebview.create');
    Route::post('/iklan-webview/posting', [\App\Http\Controllers\IklanWebviewController::class, 'store'])->name('iklanwebview.store');
    Route::get('/iklan-webview/saya', [\App\Http\Controllers\IklanWebviewController::class, 'myIklan'])->name('iklanwebview.my');
    Route::post('/iklan-webview/logout', [\App\Http\Controllers\WebviewAuthController::class, 'logout'])->name('webview.logout');
});
Route::get('/iklan-webview/login', [\App\Http\Controllers\WebviewAuthController::class, 'showLoginForm'])->name('webview.login');
Route::post('/iklan-webview/login', [\App\Http\Controllers\WebviewAuthController::class, 'login']);

// Kota Panel Auth (terpisah dari /admin/login)
Route::get('/admin/kota-login', [KotaAuthController::class, 'showLoginForm'])->name('kota.login');
Route::post('/admin/kota', [KotaAuthController::class, 'login'])->name('kota.login.post');
Route::post('/admin/kota/logout', [KotaAuthController::class, 'logout'])->name('kota.logout');

// Kota Panel (Protected: hanya MANAGER panel kota)
Route::middleware(['auth', 'role.kota'])->prefix('admin/kota')->name('kota.')->group(function () {
    Route::get('/', [KotaController::class, 'dashboard'])->name('dashboard');
    Route::get('/wilayah', [KotaController::class, 'wilayahIndex'])->name('wilayah.index');
    Route::get('/wilayah/{id}', [KotaController::class, 'wilayahDetail'])->name('wilayah.detail');

    // Coverage kota (manajemen area tanggung jawab)
    Route::get('/coverage', [KotaController::class, 'coverageIndex'])->name('coverage.index');
    Route::post('/coverage', [KotaController::class, 'coverageAdd'])->name('coverage.add');
    Route::delete('/coverage/{id}', [KotaController::class, 'coverageRemove'])->name('coverage.remove');

    // Member management sesuai coverage (merchant & driver)
    Route::get('/members', [KotaController::class, 'membersIndex'])->name('members.index');
    Route::get('/members/merchant/{id}/edit', [KotaController::class, 'membersMerchantEdit'])->name('members.merchant.edit');
    Route::put('/members/merchant/{id}', [KotaController::class, 'membersMerchantUpdate'])->name('members.merchant.update');
    Route::get('/members/driver/{id}/edit', [KotaController::class, 'membersDriverEdit'])->name('members.driver.edit');
    Route::put('/members/driver/{id}', [KotaController::class, 'membersDriverUpdate'])->name('members.driver.update');
    Route::patch('/members/driver/{id}/status', [KotaController::class, 'membersDriverStatus'])->name('members.driver.status');

    // Pengguna panel kota (hanya ADMIN super boleh mengubah role)
    Route::get('/users', [KotaController::class, 'usersIndex'])->name('users.index');
    Route::patch('/users/{id}/role', [KotaController::class, 'usersRoleUpdate'])->name('users.role.update');
});

// Admin Panel (Protected: khusus ADMIN super; Settings khusus ADMIN)
Route::middleware(['auth', 'role.panel'])->prefix('admin')->name('admin.')->group(function () {
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

    // Settings (GitHub APK artifact download links) — khusus ADMIN (bukan MANAGER)
    Route::middleware(['role.settings'])->group(function () {
        Route::get('/settings', [AdminController::class, 'settingsIndex'])->name('settings.index');
        Route::post('/settings', [AdminController::class, 'settingsUpdate'])->name('settings.update');
        Route::post('/settings/refresh-links', [AdminController::class, 'settingsRefreshLinks'])->name('settings.refresh-links');
    });

    // Iklan Gratis
    Route::get('/iklan', [AdminController::class, 'iklanGratisIndex'])->name('iklan.index');
    Route::get('/iklan/create', [AdminController::class, 'iklanGratisCreate'])->name('iklan.create');
    Route::post('/iklan', [AdminController::class, 'iklanGratisStore'])->name('iklan.store');
    Route::get('/iklan/{id}/edit', [AdminController::class, 'iklanGratisEdit'])->name('iklan.edit');
    Route::put('/iklan/{id}', [AdminController::class, 'iklanGratisUpdate'])->name('iklan.update');
    Route::delete('/iklan/{id}', [AdminController::class, 'iklanGratisDestroy'])->name('iklan.destroy');
    Route::get('/iklan/kategori', [AdminController::class, 'iklanKategoriIndex'])->name('iklan.kategori');
    Route::post('/iklan/kategori', [AdminController::class, 'iklanKategoriStore'])->name('iklan.kategori.store');
    Route::put('/iklan/kategori/{id}', [AdminController::class, 'iklanKategoriUpdate'])->name('iklan.kategori.update');
    Route::delete('/iklan/kategori/{id}', [AdminController::class, 'iklanKategoriDestroy'])->name('iklan.kategori.destroy');

    // API documentation (admin-only, removed from public)
    Route::get('/api/docs', [AdminController::class, 'apiDocs'])->name('api.docs');
});
