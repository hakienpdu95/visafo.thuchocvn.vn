<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\Api\CustomerApiController;
use Modules\Customer\Http\Controllers\CustomerContactController;
use Modules\Customer\Http\Controllers\CustomerController;
use Modules\Customer\Http\Controllers\CustomerDeliveryPointController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('customers', CustomerController::class);

    Route::post('customers/{customer}/contacts', [CustomerContactController::class, 'store'])
        ->name('customers.contacts.store');
    Route::delete('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'destroy'])
        ->name('customers.contacts.destroy');

    Route::post('customers/{customer}/delivery-points', [CustomerDeliveryPointController::class, 'store'])
        ->name('customers.delivery-points.store');
    Route::delete('customers/{customer}/delivery-points/{deliveryPoint}', [CustomerDeliveryPointController::class, 'destroy'])
        ->name('customers.delivery-points.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('customers', [CustomerApiController::class, 'index'])->name('customers');
});
