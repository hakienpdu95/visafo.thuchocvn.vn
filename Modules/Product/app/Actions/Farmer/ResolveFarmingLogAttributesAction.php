<?php

namespace Modules\Product\Actions\Farmer;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreFarmingLogData;
use Modules\Product\Models\AgriFertilizer;
use Modules\Product\Models\AgriPesticide;
use Modules\Product\Models\FarmingLog;

class ResolveFarmingLogAttributesAction
{
    use AsAction;

    public function handle(StoreFarmingLogData $data, ?string $fallbackOperator): array
    {
        $usesMaterial = in_array($data->activity_type, FarmingLog::MATERIAL_TYPES, true);
        $isHarvest = $data->activity_type === 'harvest';
        $materialName = $usesMaterial ? trim((string) $data->material_name) : null;

        $fertilizer = $data->activity_type === 'fertilizer' ? $this->findFertilizer($materialName) : null;
        $pesticide = $data->activity_type === 'pesticide' ? $this->findPesticide($materialName) : null;

        if ($fertilizer?->is_banned || $pesticide?->is_banned) {
            throw ValidationException::withMessages([
                'material_name' => 'Vật tư "' . $materialName . '" nằm trong danh mục cấm sử dụng.',
            ]);
        }

        $isolationDays = $usesMaterial ? ($data->isolation_days ?? $pesticide?->quarantine_days) : null;

        return [
            'activity_type'          => $data->activity_type,
            'activity_date'          => $data->activity_date,
            'vendor_farming_step_id' => $data->vendor_farming_step_id,
            'agri_fertilizer_id'     => $fertilizer?->id,
            'agri_pesticide_id'      => $pesticide?->id,
            'material_name'          => $materialName ?: null,
            'dosage'                 => $usesMaterial ? $data->dosage : null,
            'isolation_days'         => $isolationDays,
            'safe_harvest_date'      => $isolationDays > 0 ? Carbon::parse($data->activity_date)->addDays($isolationDays) : null,
            'method_or_target'       => $data->activity_type === 'pesticide' ? $data->method_or_target : null,
            'harvest_yield'          => $isHarvest ? $data->harvest_yield : null,
            'buyer_name'             => $isHarvest ? $data->buyer_name : null,
            'operator_name'          => $data->operator_name ?: $fallbackOperator,
            'quantity'               => null,
            'unit'                   => null,
            'notes'                  => $data->notes,
        ];
    }

    private function findFertilizer(?string $name): ?AgriFertilizer
    {
        return $name ? AgriFertilizer::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first() : null;
    }

    private function findPesticide(?string $name): ?AgriPesticide
    {
        return $name ? AgriPesticide::query()->whereRaw('LOWER(trade_name) = ?', [mb_strtolower($name)])->first() : null;
    }
}
