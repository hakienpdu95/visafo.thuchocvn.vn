<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Models\OutboundPickedBatch;

class RemovePickedBatchAction
{
    use AsAction;

    public function handle(OutboundPickedBatch $pickedBatch): void
    {
        $pickedBatch->delete();
    }
}
