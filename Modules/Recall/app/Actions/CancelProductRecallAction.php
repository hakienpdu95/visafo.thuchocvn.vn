<?php

namespace Modules\Recall\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recall\Enums\RecallStatus;
use Modules\Recall\Models\ProductRecall;

class CancelProductRecallAction
{
    use AsAction;

    public function handle(ProductRecall $recall): ProductRecall
    {
        $recall->update(['status' => RecallStatus::Cancelled->value]);

        return $recall;
    }
}
