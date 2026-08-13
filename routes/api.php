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
// Wallet GridePay (app_customer): top up, withdraw, riwayat, rekening, PIN
Route::get('/wallet/transactions', [ApiController::class, 'walletTransactions']);
Route::get('/wallet/transactions/{id}', [ApiController::class, 'walletTransactionDetail']);
Route::post('/wallet/topup', [ApiController::class, 'walletTopup']);
Route::get('/wallet/topup/{reference_no}', [ApiController::class, 'walletTopupStatus']);
Route::post('/wallet/topup/{reference_no}/complete', [ApiController::class, 'walletTopupComplete']);
Route::get('/wallet/rekening', [ApiController::class, 'walletRekening']);
Route::post('/wallet/rekening', [ApiController::class, 'walletRekeningStore']);
Route::put('/wallet/rekening/{id}', [ApiController::class, 'walletRekeningUpdate']);
Route::delete('/wallet/rekening/{id}', [ApiController::class, 'walletRekeningDelete']);
Route::post('/wallet/withdraw', [ApiController::class, 'walletWithdraw']);
Route::get('/wallet/withdraws', [ApiController::class, 'walletWithdraws']);
Route::post('/wallet/pin/set', [ApiController::class, 'walletPinSet']);
Route::post('/wallet/pin/verify', [ApiController::class, 'walletPinVerify']);
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
Route::get('/iklan-gratis/categories', [ApiController::class, 'iklanGratisCategories']);
Route::get('/iklan-gratis', [ApiController::class, 'iklanGratisIndex']);
Route::get('/iklan-gratis/{id}', [ApiController::class, 'iklanGratisShow']);
Route::post('/iklan-gratis', [ApiController::class, 'iklanGratisStore']);
Route::put('/iklan-gratis/{id}', [ApiController::class, 'iklanGratisUpdate']);
Route::delete('/iklan-gratis/{id}', [ApiController::class, 'iklanGratisDelete']);
Route::get('/ppob/webview-token', [ApiController::class, 'ppobWebviewToken']);
