<?php

use Illuminate\Support\Facades\Route;
use Modules\HanoiCheckOrder\Http\Controllers\Api\HanoiCheckOrderApiController;
use Modules\HanoiCheckOrder\Http\Controllers\HanoiCheckOrderController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('hanoicheck-orders', [HanoiCheckOrderController::class, 'index'])->name('hanoicheck-orders.index');
    Route::put('hanoicheck-orders/{sales_order}/link', [HanoiCheckOrderController::class, 'link'])->name('hanoicheck-orders.link');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('hanoicheck-orders', [HanoiCheckOrderApiController::class, 'index'])->name('hanoicheck-orders');
});
