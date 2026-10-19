<?php

namespace Modules\Product\Actions\Backend;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Actions\Farmer\ResolveFarmingLogAttributesAction;
use Modules\Product\Data\Requests\StoreFarmingLogData;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingLog;
use Modules\Product\Support\VietGapLogParser;

class ImportFarmingLogsAction
{
    use AsAction;

    public function __construct(
        private VietGapLogParser $parser,
        private ResolveFarmingLogAttributesAction $resolveAttributes,
    ) {}

    public function handle(FarmingBatch $farmingBatch, string $path, string $sheetName, string $userId): array
    {
        $parsed = $this->parser->parse($path, $sheetName, $farmingBatch->sowing_date);
        $errors = $parsed['errors'];
        $warnings = $parsed['warnings'];
        $created = 0;
        $duplicates = 0;

        DB::transaction(function () use ($farmingBatch, $parsed, $userId, &$errors, &$created, &$duplicates) {
            foreach ($parsed['rows'] as $row) {
                $data = StoreFarmingLogData::from([
                    'vendor_farming_step_id' => null,
                    'method_or_target'       => null,
                    'buyer_name'             => null,
                    'harvest_yield'          => null,
                    'material_name'          => null,
                    'dosage'                 => null,
                    'isolation_days'         => null,
                    'image'                  => null,
                    ...collect($row)->except('row')->all(),
                ]);

                try {
                    $attributes = $this->resolveAttributes->handle($data, null);
                } catch (ValidationException $e) {
                    $errors[] = 'Dòng ' . $row['row'] . ': ' . $e->validator->errors()->first();
                    continue;
                }

                if ($this->isDuplicate($farmingBatch, $attributes)) {
                    $duplicates++;
                    continue;
                }

                FarmingLog::query()->create([
                    ...$attributes,
                    'farming_batch_id' => $farmingBatch->id,
                    'created_by'       => $userId,
                ]);
                $created++;
            }

            $lastHarvest = $farmingBatch->logs()->where('activity_type', 'harvest')->max('activity_date');
            if ($lastHarvest && $farmingBatch->status === 'active') {
                $farmingBatch->update([
                    'actual_harvest_date' => Carbon::parse($lastHarvest)->toDateString(),
                    'status'              => 'harvested',
                ]);
            }

            $farmingBatch->revokeHarvestApprovalIfQuarantined();
        });

        return [
            'created'    => $created,
            'duplicates' => $duplicates,
            'errors'     => $errors,
            'warnings'   => [...$warnings, ...$this->quarantineViolations($farmingBatch)],
        ];
    }

    private function isDuplicate(FarmingBatch $farmingBatch, array $attributes): bool
    {
        return FarmingLog::query()
            ->where('farming_batch_id', $farmingBatch->id)
            ->where('activity_type', $attributes['activity_type'])
            ->whereDate('activity_date', Carbon::parse($attributes['activity_date'])->toDateString())
            ->where(fn ($q) => $attributes['material_name'] === null
                ? $q->whereNull('material_name')
                : $q->where('material_name', $attributes['material_name']))
            ->exists();
    }

    private function quarantineViolations(FarmingBatch $farmingBatch): array
    {
        $logs = $farmingBatch->logs()->get(['activity_type', 'activity_date', 'safe_harvest_date', 'material_name']);

        return $logs->where('activity_type', 'harvest')
            ->map(function (FarmingLog $harvest) use ($logs) {
                $blocking = $logs
                    ->filter(fn (FarmingLog $l) => $l->safe_harvest_date
                        && $l->activity_date->lt($harvest->activity_date)
                        && $l->safe_harvest_date->gt($harvest->activity_date->copy()->startOfDay()))
                    ->sortByDesc('safe_harvest_date')
                    ->first();

                return $blocking
                    ? 'Thu hoạch ngày ' . $harvest->activity_date->format('d/m/Y') . ' nằm trong thời gian cách ly của "'
                        . ($blocking->material_name ?? 'vật tư') . '" (an toàn từ ' . $blocking->safe_harvest_date->format('d/m/Y') . ').'
                    : null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
