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
Route::get('/settings', [ApiController::class, 'settings']);
Route::post('/orders', [ApiController::class, 'ordersStore']);
Route::post('/register', [ApiController::class, 'register']);
Route::post('/login', [ApiController::class, 'login']);
Route::post('/drivers/{id}/location', [ApiController::class, 'driverLocationUpdate']);
