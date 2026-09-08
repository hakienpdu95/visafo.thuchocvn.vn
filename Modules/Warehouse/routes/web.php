<?php

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Http\Controllers\Api\BatchApiController;
use Modules\Warehouse\Http\Controllers\Api\RetailItemTagApiController;
use Modules\Warehouse\Http\Controllers\Api\InboundReceiptApiController;
use Modules\Warehouse\Http\Controllers\Api\OutboundOrderApiController;
use Modules\Warehouse\Http\Controllers\Api\SapoProductSyncLogApiController;
use Modules\Warehouse\Http\Controllers\Api\SapoSyncLogApiController;
use Modules\Warehouse\Http\Controllers\Api\TagRollApiController;
use Modules\Warehouse\Http\Controllers\BatchController;
use Modules\Warehouse\Http\Controllers\BatchDocumentController;
use Modules\Warehouse\Http\Controllers\BatchInReceiptController;
use Modules\Warehouse\Http\Controllers\InboundReceiptController;
use Modules\Warehouse\Http\Controllers\InboundReceiptDocumentController;
use Modules\Warehouse\Http\Controllers\OutboundOrderController;
use Modules\Warehouse\Http\Controllers\RetailItemTagController;
use Modules\Warehouse\Http\Controllers\SapoSyncLogController;
use Modules\Warehouse\Http\Controllers\SerialLookupController;
use Modules\Warehouse\Http\Controllers\TagProvisioningController;
use Modules\Warehouse\Http\Controllers\TagScanBindController;
use Modules\Warehouse\Http\Controllers\TraceabilityPortalController;

Route::get('qr/01/{sku}/10/{batchCode}/21/{serial}', [TraceabilityPortalController::class, 'show'])
    ->where('serial', '[0-9]+')
    ->name('portal.trace');

Route::get('id/ser/{uid}', [TraceabilityPortalController::class, 'showByUid'])
    ->name('portal.trace.uid');

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('inbound-receipts', InboundReceiptController::class);

    Route::post('inbound-receipts/{inbound_receipt}/complete', [InboundReceiptController::class, 'complete'])
        ->name('inbound-receipts.complete');

    Route::post('inbound-receipts/{inbound_receipt}/documents', [InboundReceiptDocumentController::class, 'store'])
        ->name('inbound-receipts.documents.store');
    Route::delete('inbound-receipts/{inbound_receipt}/documents/{document}', [InboundReceiptDocumentController::class, 'destroy'])
        ->name('inbound-receipts.documents.destroy');

    Route::post('inbound-receipts/{inbound_receipt}/batches', [BatchInReceiptController::class, 'store'])
        ->name('inbound-receipts.batches.store');

    Route::get('batches', [BatchController::class, 'index'])->name('batches.index');
    Route::get('batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::post('batches/{batch}/recall', [BatchController::class, 'recall'])->name('batches.recall');
    Route::post('batches/{batch}/bind-tags-range', [BatchController::class, 'bindTagsRange'])->name('batches.bind-tags-range');
    Route::post('batches/{batch}/activate-tags', [BatchController::class, 'activateTags'])->name('batches.activate-tags');
    Route::post('batches/{batch}/unbind-tag-range', [BatchController::class, 'unbindTagRange'])->name('batches.unbind-tag-range');

    Route::post('batches/{batch}/documents', [BatchDocumentController::class, 'store'])
        ->name('batches.documents.store');
    Route::delete('batches/{batch}/documents/{document}', [BatchDocumentController::class, 'destroy'])
        ->name('batches.documents.destroy');

    Route::get('batches/{batch}/tags', [RetailItemTagController::class, 'index'])->name('batches.tags.index');
    Route::get('batches/{batch}/tags/print', [RetailItemTagController::class, 'print'])->name('batches.tags.print');

    Route::get('batches/{batch}/audit-trail', [BatchController::class, 'auditTrail'])->name('batches.audit-trail');
    Route::get('batches/{batch}/sapo-sync-log', [BatchController::class, 'sapoSyncLog'])->name('batches.sapo-sync-log');
    Route::get('batches/{batch}/incidents', [BatchController::class, 'incidents'])->name('batches.incidents');

    Route::post('tags/{tag}/unbind', [RetailItemTagController::class, 'unbind'])->name('tags.unbind');
    Route::post('tags/{tag}/void', [RetailItemTagController::class, 'void'])->name('tags.void');

    Route::get('serial-lookup', [SerialLookupController::class, 'index'])->name('serial-lookup.index');

    Route::get('sapo-sync-log', [SapoSyncLogController::class, 'index'])->name('sapo-sync-log.index');

    Route::get('tag-scan-bind', [TagScanBindController::class, 'create'])->name('tag-scan-bind.create');
    Route::post('tag-scan-bind/scan', [TagScanBindController::class, 'scan'])->name('tag-scan-bind.scan');

    Route::get('tag-rolls', [TagProvisioningController::class, 'index'])->name('tag-rolls.index');
    Route::post('tag-rolls', [TagProvisioningController::class, 'provision'])->name('tag-rolls.provision');
    Route::get('tag-rolls/download-csv', [TagProvisioningController::class, 'downloadCsv'])->name('tag-rolls.download-csv');
    Route::get('tag-rolls/download-pdf', [TagProvisioningController::class, 'downloadPdf'])->name('tag-rolls.download-pdf');
    Route::get('tag-rolls/{roll}', [TagProvisioningController::class, 'show'])->name('tag-rolls.show');

    Route::resource('outbound-orders', OutboundOrderController::class)
        ->parameters(['outbound-orders' => 'order'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::post('outbound-orders/{order}/batches', [OutboundOrderController::class, 'addBatch'])->name('outbound-orders.batches.store');
    Route::delete('outbound-orders/{order}/batches/{pickedBatch}', [OutboundOrderController::class, 'removeBatch'])->name('outbound-orders.batches.destroy');
    Route::post('outbound-orders/{order}/complete', [OutboundOrderController::class, 'complete'])->name('outbound-orders.complete');
    Route::post('outbound-orders/{order}/activate-tags', [OutboundOrderController::class, 'activateTags'])->name('outbound-orders.activate-tags');
    Route::post('outbound-orders/{order}/cancel', [OutboundOrderController::class, 'cancel'])->name('outbound-orders.cancel');
    Route::get('outbound-orders/{order}/packing-slip', [OutboundOrderController::class, 'packingSlip'])->name('outbound-orders.packing-slip');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('batches', [BatchApiController::class, 'index'])->name('batches');
    Route::get('batches/{batch}/tags', [RetailItemTagApiController::class, 'index'])->name('batches.tags');
    Route::get('inbound-receipts', [InboundReceiptApiController::class, 'index'])->name('inbound-receipts');
    Route::get('outbound-orders', [OutboundOrderApiController::class, 'index'])->name('outbound-orders');
    Route::get('sapo-sync-log', [SapoSyncLogApiController::class, 'index'])->name('sapo-sync-log');
    Route::get('sapo-product-sync-log', [SapoProductSyncLogApiController::class, 'index'])->name('sapo-product-sync-log');
    Route::get('tag-rolls', [TagRollApiController::class, 'index'])->name('tag-rolls');
});
