<?php

namespace Modules\Product\Http\Controllers\Farmer;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Farmer\StoreFarmingLogAction;
use Modules\Product\Actions\Farmer\UpdateFarmingLogAction;
use Modules\Product\Data\Requests\StoreFarmingLogData;
use Modules\Product\Models\AgriFertilizer;
use Modules\Product\Models\AgriPesticide;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingLog;
use Modules\Product\Models\VendorFarmingStep;

class FarmingLogController extends Controller
{
    public function create(FarmingBatch $farmingBatch)
    {
        $this->authorizeOwnership($farmingBatch);

        return view('product::farmer.logs.create', [
            'farmingBatch'       => $farmingBatch,
            'materials'          => $this->materialSuggestions(),
            'steps'              => $this->buildSteps($farmingBatch),
            'quarantineDaysLeft' => $farmingBatch->quarantineDaysLeft(),
        ]);
    }

    public function store(Request $request, FarmingBatch $farmingBatch, StoreFarmingLogAction $action): RedirectResponse
    {
        $this->authorizeOwnership($farmingBatch);

        $data = StoreFarmingLogData::validateAndCreate($request->all());
        $action->handle($farmingBatch, $data, auth()->id(), auth()->user()->name);

        return $this->redirectAfterMutation($farmingBatch, 'Đã ghi nhật ký cho lô "' . $farmingBatch->batch_code . '".');
    }

    public function edit(FarmingLog $farmingLog)
    {
        $this->authorizeMutation($farmingLog);
        $farmingLog->load('batch', 'agriFertilizer', 'agriPesticide', 'creator');

        $steps = $this->buildSteps($farmingLog->batch);

        $currentKey = $farmingLog->vendor_farming_step_id
            ? 'custom_' . $farmingLog->vendor_farming_step_id
            : $farmingLog->activity_type;

        if (!collect($steps)->contains('key', $currentKey)) {
            array_unshift($steps, [
                'key'                    => $currentKey,
                'label'                  => $farmingLog->typeLabel(),
                'activity_type'          => $farmingLog->activity_type,
                'shell'                  => 'legacy',
                'color'                  => 'btn-neutral',
                'vendor_farming_step_id' => $farmingLog->vendor_farming_step_id,
            ]);
        }

        return view('product::farmer.logs.edit', [
            'farmingLog'         => $farmingLog,
            'farmingBatch'       => $farmingLog->batch,
            'materials'          => $this->materialSuggestions(),
            'steps'              => $steps,
            'currentKey'         => $currentKey,
            'quarantineDaysLeft' => $farmingLog->batch->quarantineDaysLeft(),
        ]);
    }

    public function update(Request $request, FarmingLog $farmingLog, UpdateFarmingLogAction $action): RedirectResponse
    {
        $this->authorizeMutation($farmingLog);

        $data = StoreFarmingLogData::validateAndCreate($request->all());
        $action->handle($farmingLog, $data);

        return $this->redirectAfterMutation($farmingLog->batch, 'Đã cập nhật nhật ký.');
    }

    public function destroy(FarmingLog $farmingLog): RedirectResponse
    {
        $this->authorizeMutation($farmingLog);

        $farmingBatch = $farmingLog->batch;
        $farmingLog->delete();

        return $this->redirectAfterMutation($farmingBatch, 'Đã xóa nhật ký.');
    }

    private function buildSteps(FarmingBatch $farmingBatch): array
    {
        $hardShell = collect(FarmingLog::ACTIVITY_TYPES)
            ->except('cultivation')
            ->map(fn (array $type, string $key) => [
                'key'                    => $key,
                'label'                  => $type['label'],
                'activity_type'          => $key,
                'shell'                  => 'hard',
                'color'                  => $type['btn'],
                'vendor_farming_step_id' => null,
            ])
            ->values()
            ->all();

        $softShell = VendorFarmingStep::query()
            ->where('vendor_id', $farmingBatch->vendor_id)
            ->where(function ($q) use ($farmingBatch) {
                $q->whereNull('partner_product_id')
                    ->orWhere('partner_product_id', $farmingBatch->partner_product_id);
            })
            ->orderBy('order_index')
            ->get()
            ->map(fn (VendorFarmingStep $step) => [
                'key'                    => 'custom_' . $step->id,
                'label'                  => $step->step_name,
                'activity_type'          => $step->base_activity_type,
                'shell'                  => 'soft',
                'color'                  => 'btn-info',
                'vendor_farming_step_id' => $step->id,
            ])
            ->all();

        return array_merge($hardShell, $softShell);
    }

    private function materialSuggestions(): array
    {
        $fertilizers = AgriFertilizer::query()
            ->where('status', 'active')
            ->where('is_banned', false)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (string $name) => ['name' => $name, 'quarantine_days' => null]);

        $pesticides = AgriPesticide::query()
            ->where('status', 'active')
            ->where('is_banned', false)
            ->orderBy('trade_name')
            ->get(['trade_name', 'quarantine_days'])
            ->map(fn (AgriPesticide $p) => ['name' => $p->trade_name, 'quarantine_days' => $p->quarantine_days]);

        return [
            'fertilizer' => $fertilizers->values()->all(),
            'pesticide'  => $pesticides->values()->all(),
        ];
    }

    private function redirectAfterMutation(FarmingBatch $farmingBatch, string $message): RedirectResponse
    {
        if (auth()->user()->hasRole(RoleEnum::FARMER->value)) {
            return redirect()->route('farmer.dashboard')->with('success', $message);
        }

        return redirect()->route('backend.farming-batches.show', $farmingBatch)->with('success', $message);
    }

    private function authorizeOwnership(FarmingBatch $farmingBatch): void
    {
        $user = auth()->user();

        if ($user->hasRole(RoleEnum::FARMER->value)) {
            abort_unless($farmingBatch->vendor_id === $user->vendor_id, 403);
            return;
        }

        abort_unless($user->can('compliance.manage'), 403);
    }

    private function authorizeMutation(FarmingLog $farmingLog): void
    {
        $user = auth()->user();

        if ($user->can('compliance.manage')) {
            return;
        }

        abort_unless($user->hasRole(RoleEnum::FARMER->value), 403);
        abort_unless($farmingLog->batch->vendor_id === $user->vendor_id, 403);
        abort_unless($farmingLog->created_at->isAfter(now()->subHours(24)), 403);
    }
}
