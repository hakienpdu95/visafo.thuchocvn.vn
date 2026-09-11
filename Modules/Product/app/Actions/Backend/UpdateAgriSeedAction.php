<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\UpdateAgriSeedData;
use Modules\Product\Models\AgriSeed;

class UpdateAgriSeedAction
{
    use AsAction;

    public function handle(AgriSeed $agriSeed, UpdateAgriSeedData $data): AgriSeed
    {
        $agriSeed->update([
            'is_banned' => $data->is_banned,
        ]);

        return $agriSeed->fresh();
    }
}
