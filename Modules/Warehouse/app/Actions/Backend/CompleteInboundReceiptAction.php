<?php

namespace Modules\Warehouse\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\InboundReceiptStatus;
use Modules\Warehouse\Models\InboundReceipt;

class CompleteInboundReceiptAction
{
    use AsAction;

    public function handle(InboundReceipt $inboundReceipt): InboundReceipt
    {
        DB::transaction(function () use ($inboundReceipt) {
            $inboundReceipt->update(['status' => InboundReceiptStatus::Completed->value]);
        });

        return $inboundReceipt->fresh();
    }
}
