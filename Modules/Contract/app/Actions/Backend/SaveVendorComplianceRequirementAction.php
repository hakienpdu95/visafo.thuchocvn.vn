<?php

namespace Modules\Contract\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Contract\Data\Requests\SaveVendorComplianceRequirementData;
use Modules\Contract\Enums\ComplianceRequirementKind;
use Modules\Contract\Models\VendorComplianceRequirement;

class SaveVendorComplianceRequirementAction
{
    use AsAction;

    public function handle(SaveVendorComplianceRequirementData $data, ?string $groupKey = null): string
    {
        return DB::transaction(function () use ($data, $groupKey) {
            $existing = $groupKey !== null
                ? VendorComplianceRequirement::query()->inGroup($groupKey)->get()
                : collect();

            $newKey    = $existing->first()?->group_key ?? (string) Str::ulid();
            $targetCol = $data->kind === ComplianceRequirementKind::Contract ? 'contract_type_id' : 'document_master_type_id';

            $attributes = [
                'kind'         => $data->kind->value,
                'group_key'    => $newKey,
                'label'        => $data->label,
                'source_group' => $data->source_group?->value,
                'is_mandatory' => $data->is_mandatory,
                'warning_days' => $data->warning_days,
                'legal_basis'  => $data->legal_basis,
            ];

            $kept = collect();
            foreach ($existing as $row) {
                $targetId = $row->kind === $data->kind ? $row->{$targetCol} : null;

                if ($targetId !== null && in_array($targetId, $data->target_ids, true) && ! $kept->contains($targetId)) {
                    $row->update($attributes);
                    $kept->push($targetId);
                } else {
                    $row->delete();
                }
            }

            foreach (array_diff($data->target_ids, $kept->all()) as $targetId) {
                VendorComplianceRequirement::query()->create($attributes + [
                    'document_master_type_id' => $targetCol === 'document_master_type_id' ? $targetId : null,
                    'contract_type_id'        => $targetCol === 'contract_type_id' ? $targetId : null,
                ]);
            }

            return $newKey;
        });
    }
}
