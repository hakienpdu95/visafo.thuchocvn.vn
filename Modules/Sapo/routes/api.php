<?php

use Illuminate\Support\Facades\Route;
use Modules\Sapo\Http\Controllers\SapoOrderWebhookController;
use Modules\Sapo\Http\Controllers\SapoProductWebhookController;

Route::middleware('sapo.hmac')->group(function () {
    Route::post('webhooks/sapo/{org_id}/orders-create', [SapoOrderWebhookController::class, 'handle'])
        ->name('webhooks.sapo.orders-create');

    Route::post('webhooks/sapo/{org_id}/products-create', [SapoProductWebhookController::class, 'create'])
        ->name('webhooks.sapo.products-create');
    Route::post('webhooks/sapo/{org_id}/products-update', [SapoProductWebhookController::class, 'update'])
        ->name('webhooks.sapo.products-update');
    Route::post('webhooks/sapo/{org_id}/products-delete', [SapoProductWebhookController::class, 'delete'])
        ->name('webhooks.sapo.products-delete');
});
