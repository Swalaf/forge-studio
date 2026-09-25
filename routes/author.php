<?php

use App\Http\Controllers\Author\AcquisitionController;
use App\Http\Controllers\Author\AnalyticsController;
use App\Http\Controllers\Author\OverviewController;
use App\Http\Controllers\Author\PayoutController;
use App\Http\Controllers\Author\ProductController;
use App\Http\Controllers\Author\ReviewController;
use App\Http\Controllers\Author\SaleController;
use App\Http\Controllers\Author\SettingController;
use App\Http\Controllers\Author\SubmissionController;
use App\Http\Controllers\Author\SupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('author')->name('author.')->middleware(['auth', 'role:author'])->group(function () {
    Route::get('/', OverviewController::class)->name('overview');

    Route::get('/acquisitions', [AcquisitionController::class, 'index'])->name('acquisitions.index');
    Route::get('/acquisitions/create', [AcquisitionController::class, 'create'])->name('acquisitions.create');
    Route::post('/acquisitions', [AcquisitionController::class, 'store'])->name('acquisitions.store');
    Route::get('/acquisitions/{product}/edit', [AcquisitionController::class, 'edit'])->name('acquisitions.edit');
    Route::put('/acquisitions/{product}', [AcquisitionController::class, 'update'])->name('acquisitions.update');
    Route::post('/acquisitions/offers/{offer}/respond', [AcquisitionController::class, 'respondToOffer'])->name('acquisitions.offers.respond');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::post('/products/{product}/submit-version', [ProductController::class, 'submitVersion'])->name('products.submit-version');

    Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/{productVersion}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::post('/submissions/{productVersion}/withdraw', [SubmissionController::class, 'withdraw'])->name('submissions.withdraw');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/reply', [ReviewController::class, 'reply'])->name('reviews.reply');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/support/{ticket}', [SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportController::class, 'reply'])->name('support.reply');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});
