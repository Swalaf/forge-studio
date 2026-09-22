<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketController::class, 'home'])->name('home');
Route::get('/market', [MarketController::class, 'browse'])->name('market.browse');
Route::get('/market/{product:slug}', [MarketController::class, 'show'])->name('market.show');

Route::get('/hire-us', [ServiceRequestController::class, 'create'])->name('hire-us');
Route::post('/hire-us', [ServiceRequestController::class, 'store'])->name('hire-us.store');

Route::post('/market/{product:slug}/reviews', [ReviewController::class, 'store'])
    ->middleware(['auth', 'role:customer'])
    ->name('reviews.store');

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/market/{product:slug}/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/market/{product:slug}/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
});
Route::get('/checkout/{order}/success', [CheckoutController::class, 'success'])->middleware('auth')->name('checkout.success');

// Gateways call these directly — no CSRF, verified by signature instead.
Route::post('/webhooks/stripe', StripeWebhookController::class)->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->name('webhooks.stripe');
Route::post('/webhooks/paystack', PaystackWebhookController::class)->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->name('webhooks.paystack');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
