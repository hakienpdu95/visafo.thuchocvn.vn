<?php

use Illuminate\Support\Facades\Route;
use Modules\Recall\Http\Controllers\AdverseEventReportController;
use Modules\Recall\Http\Controllers\ProductRecallController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('product-recalls', ProductRecallController::class)
        ->parameters(['product-recalls' => 'recall'])
        ->only(['index', 'create', 'store', 'show']);
    Route::post('product-recalls/{recall}/complete', [ProductRecallController::class, 'complete'])->name('product-recalls.complete');
    Route::post('product-recalls/{recall}/cancel', [ProductRecallController::class, 'cancel'])->name('product-recalls.cancel');

    Route::post('adverse-event-reports/lookup-tag', [AdverseEventReportController::class, 'lookupTag'])->name('adverse-event-reports.lookup-tag');

    Route::resource('adverse-event-reports', AdverseEventReportController::class)
        ->parameters(['adverse-event-reports' => 'report'])
        ->only(['index', 'create', 'store', 'show']);
    Route::post('adverse-event-reports/{report}/submit', [AdverseEventReportController::class, 'submit'])->name('adverse-event-reports.submit');
    Route::post('adverse-event-reports/{report}/close', [AdverseEventReportController::class, 'close'])->name('adverse-event-reports.close');
    Route::get('adverse-event-reports/{report}/pdf', [AdverseEventReportController::class, 'printPdf'])->name('adverse-event-reports.pdf');
});
