<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthorController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\LicenseController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ReviewQueueController;
use App\Http\Controllers\Admin\ServiceRequestController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', OverviewController::class)->name('overview');

    Route::get('/review', [ReviewQueueController::class, 'index'])->name('review.index');
    Route::get('/review/{productVersion}', [ReviewQueueController::class, 'show'])->name('review.show');
    Route::post('/review/{productVersion}/approve', [ReviewQueueController::class, 'approve'])->name('review.approve');
    Route::post('/review/{productVersion}/request-changes', [ReviewQueueController::class, 'requestChanges'])->name('review.request-changes');
    Route::post('/review/{productVersion}/reject', [ReviewQueueController::class, 'reject'])->name('review.reject');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::post('/products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
    Route::post('/products/{product}/hide', [ProductController::class, 'hide'])->name('products.hide');

    Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
    Route::get('/authors/{author}', [AuthorController::class, 'show'])->name('authors.show');
    Route::post('/authors/{author}/approve', [AuthorController::class, 'approve'])->name('authors.approve');
    Route::post('/authors/{author}/suspend', [AuthorController::class, 'suspend'])->name('authors.suspend');
    Route::put('/authors/{author}/tier', [AuthorController::class, 'updateTier'])->name('authors.tier');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');

    Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
    Route::post('/licenses/{license}/revoke', [LicenseController::class, 'revoke'])->name('licenses.revoke');
    Route::post('/licenses/{license}/renew', [LicenseController::class, 'renew'])->name('licenses.renew');

    Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/run-batch', [PayoutController::class, 'runBatch'])->name('payouts.run-batch');
    Route::post('/payouts/{payout}/hold', [PayoutController::class, 'hold'])->name('payouts.hold');
    Route::post('/payouts/{payout}/release', [PayoutController::class, 'release'])->name('payouts.release');

    Route::get('/requests', [ServiceRequestController::class, 'index'])->name('requests.index');
    Route::post('/requests/{serviceRequest}/convert', [ServiceRequestController::class, 'convert'])->name('requests.convert');
    Route::post('/requests/{serviceRequest}/decline', [ServiceRequestController::class, 'decline'])->name('requests.decline');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/milestones/{milestone}', [ProjectController::class, 'updateMilestone'])->name('projects.milestones.update');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');

    Route::get('/content', [ContentController::class, 'edit'])->name('content.edit');
    Route::put('/content', [ContentController::class, 'update'])->name('content.update');

    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});
