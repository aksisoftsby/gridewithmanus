<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::get('/merchants', [ApiController::class, 'merchants']);
Route::get('/merchants/{id}/menu', [ApiController::class, 'merchantMenu']);
Route::get('/products', [ApiController::class, 'products']);
Route::get('/orders', [ApiController::class, 'orders']);
Route::get('/promos', [ApiController::class, 'promos']);
Route::get('/news', [ApiController::class, 'news']);
Route::get('/testimonials', [ApiController::class, 'testimonials']);
Route::get('/wallets', [ApiController::class, 'wallets']);
Route::get('/settings', [ApiController::class, 'settings']);
Route::post('/orders', [ApiController::class, 'ordersStore']);
Route::post('/register', [ApiController::class, 'register']);
Route::post('/register-driver', [ApiController::class, 'registerDriver']);
Route::post('/register-merchant', [ApiController::class, 'registerMerchant']);
Route::get('/merchant/me', [ApiController::class, 'merchantMe']);
Route::post('/merchant/update', [ApiController::class, 'merchantUpdate']);
Route::post('/products', [ApiController::class, 'storeProduct']);
Route::put('/products/{id}', [ApiController::class, 'updateProduct']);
Route::delete('/products/{id}', [ApiController::class, 'toggleProduct']);
Route::get('/merchant/earnings', [ApiController::class, 'merchantEarnings']);
Route::get('/driver/me', [ApiController::class, 'driverMe']);
Route::get('/driver/earnings', [ApiController::class, 'driverEarnings']);
Route::post('/login', [ApiController::class, 'login']);
Route::post('/drivers/{id}/location', [ApiController::class, 'driverLocationUpdate']);
