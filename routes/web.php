<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketController::class, 'home'])->name('home');
Route::get('/market', [MarketController::class, 'browse'])->name('market.browse');
Route::get('/market/{product:slug}', [MarketController::class, 'show'])->name('market.show');

Route::get('/hire-us', [ServiceRequestController::class, 'create'])->name('hire-us');
Route::post('/hire-us', [ServiceRequestController::class, 'store'])->name('hire-us.store');

Route::post('/market/{product:slug}/reviews', [ReviewController::class, 'store'])
    ->middleware(['auth', 'role:customer'])
    ->name('reviews.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
