<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\FarmingSource;

class ConfirmFarmingSourcePreSeasonAction
{
    use AsAction;

    public function handle(FarmingSource $farmingSource, int|string $checkedByUserId): FarmingSource
    {
        $farmingSource->update([
            'status'                 => 'passed',
            'pre_season_checked_at'  => now(),
            'pre_season_checked_by'  => $checkedByUserId,
        ]);

        return $farmingSource->fresh();
    }
}
