<?php

use Illuminate\Support\Facades\Route;
use Modules\Contract\Http\Controllers\Api\ContractApiController;
use Modules\Contract\Http\Controllers\ContractController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('contracts', ContractController::class);
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('contracts', [ContractApiController::class, 'index'])->name('contracts');
});
