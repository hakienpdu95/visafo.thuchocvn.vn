<?php

namespace Modules\Contract\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Contract\Models\VendorComplianceRequirement;

class DestroyVendorComplianceRequirementAction
{
    use AsAction;

    public function handle(string $groupKey): string
    {
        return DB::transaction(function () use ($groupKey) {
            $rows = VendorComplianceRequirement::query()->inGroup($groupKey)->get();
            abort_if($rows->isEmpty(), 404);

            $rows->each->delete();

            return $rows->first()->label;
        });
    }
}
