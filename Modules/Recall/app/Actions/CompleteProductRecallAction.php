<?php

namespace Modules\Recall\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recall\Enums\RecallStatus;
use Modules\Recall\Models\ProductRecall;

class CompleteProductRecallAction
{
    use AsAction;

    public function handle(ProductRecall $recall): ProductRecall
    {
        $recall->update([
            'status'       => RecallStatus::Completed->value,
            'completed_at' => now(),
        ]);

        return $recall;
    }
}
