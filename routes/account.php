<?php

use App\Http\Controllers\Account\AcquisitionController;
use App\Http\Controllers\Account\DownloadController;
use App\Http\Controllers\Account\InvoiceController;
use App\Http\Controllers\Account\LicenseController;
use App\Http\Controllers\Account\OverviewController;
use App\Http\Controllers\Account\PurchaseController;
use App\Http\Controllers\Account\SavedController;
use App\Http\Controllers\Account\ServiceController;
use App\Http\Controllers\Account\SettingController;
use App\Http\Controllers\Account\SupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')->middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/', OverviewController::class)->name('overview');

    Route::get('/acquisitions', [AcquisitionController::class, 'index'])->name('acquisitions.index');

    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');

    Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads.index');
    Route::get('/downloads/{license}', [DownloadController::class, 'download'])->name('downloads.download');

    Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
    Route::post('/licenses/{license}/renew', [LicenseController::class, 'renew'])->name('licenses.renew');

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{project}', [ServiceController::class, 'show'])->name('services.show');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{order}', [InvoiceController::class, 'show'])->name('invoices.show');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/support/create', [SupportController::class, 'create'])->name('support.create');
    Route::post('/support', [SupportController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportController::class, 'reply'])->name('support.reply');

    Route::get('/saved', [SavedController::class, 'index'])->name('saved.index');
    Route::post('/saved/{product}', [SavedController::class, 'store'])->name('saved.store');
    Route::delete('/saved/{product}', [SavedController::class, 'destroy'])->name('saved.destroy');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});
