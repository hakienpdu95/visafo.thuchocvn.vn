<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Models\InboundReceipt;

class DestroyInboundReceiptAction
{
    use AsAction;

    public function handle(InboundReceipt $inboundReceipt): string
    {
        $number = $inboundReceipt->receipt_number;
        $inboundReceipt->delete();

        return $number;
    }
}
