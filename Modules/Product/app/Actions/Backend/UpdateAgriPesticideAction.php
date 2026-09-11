<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\UpdateAgriPesticideData;
use Modules\Product\Models\AgriPesticide;

class UpdateAgriPesticideAction
{
    use AsAction;

    public function handle(AgriPesticide $agriPesticide, UpdateAgriPesticideData $data): AgriPesticide
    {
        $agriPesticide->update([
            'quarantine_days' => $data->quarantine_days,
            'is_banned'       => $data->is_banned,
        ]);

        return $agriPesticide->fresh();
    }
}
