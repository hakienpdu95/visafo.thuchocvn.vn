<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Product\Actions\Backend\ApproveFarmingBatchHarvestAction;
use Modules\Product\Actions\Backend\DestroyFarmingBatchAction;
use Modules\Product\Actions\Backend\StoreFarmingBatchAction;
use Modules\Product\Actions\Backend\UpdateFarmingBatchAction;
use Modules\Product\Data\Requests\StoreFarmingBatchData;
use Modules\Product\Models\AgriSeed;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingSource;
use Modules\Product\Models\PartnerProduct;

class FarmingBatchController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(FarmingBatch::class, 'farming_batch');
    }

    public function index()
    {
        $farmingSources = FarmingSource::query()
            ->where('status', 'passed')
            ->with('vendor:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'vendor_id']);

        $agriSeeds = AgriSeed::query()
            ->where('status', 'active')
            ->where('is_banned', false)
            ->orderBy('name')
            ->get(['id', 'crop_type', 'name']);

        $partnerProducts = PartnerProduct::query()
            ->orderBy('name')
            ->get(['id', 'name', 'vendor_id']);

        return view('product::farming_batches.index', compact('farmingSources', 'agriSeeds', 'partnerProducts'));
    }

    public function store(Request $request, StoreFarmingBatchAction $action): RedirectResponse
    {
        $data = StoreFarmingBatchData::validateAndCreate($request->all());
        $batch = $action->handle($data);

        return redirect()->route('backend.farming-batches.show', $batch)
            ->with('success', 'Đã mở vụ/lô "' . $batch->batch_code . '".');
    }

    public function update(Request $request, FarmingBatch $farmingBatch, UpdateFarmingBatchAction $action): RedirectResponse
    {
        $data = StoreFarmingBatchData::validateAndCreate($request->all());
        $action->handle($farmingBatch, $data);

        return redirect()->route('backend.farming-batches.show', $farmingBatch)
            ->with('success', 'Đã cập nhật vụ/lô "' . $farmingBatch->batch_code . '".');
    }

    public function destroy(FarmingBatch $farmingBatch, DestroyFarmingBatchAction $action): JsonResponse
    {
        try {
            $code = $action->handle($farmingBatch);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        }

        return response()->json(['message' => 'Đã xóa vụ/lô "' . $code . '".']);
    }

    public function show(FarmingBatch $farmingBatch)
    {
        $farmingBatch->load([
            'farmingSource', 'vendor', 'agriSeed', 'partnerProduct', 'preHarvestCheckedBy',
            'logs.agriFertilizer', 'logs.agriPesticide', 'logs.vendorFarmingStep', 'logs.creator',
        ]);

        $pendingQuarantineLogs = $farmingBatch->pendingQuarantineLogs()->with('agriPesticide')->get();
        $isReadyForHarvest = $pendingQuarantineLogs->isEmpty();

        return view('product::farming_batches.show', compact(
            'farmingBatch', 'pendingQuarantineLogs', 'isReadyForHarvest'
        ));
    }

    public function approveHarvest(FarmingBatch $farmingBatch, ApproveFarmingBatchHarvestAction $action): RedirectResponse
    {
        $this->authorize('approveHarvest', $farmingBatch);

        if (!$farmingBatch->isReadyForHarvest()) {
            return redirect()->route('backend.farming-batches.show', $farmingBatch)
                ->with('error', 'Lô này chưa hết thời gian cách ly — không thể phê duyệt thu hoạch.');
        }

        $action->handle($farmingBatch, auth()->id());

        return redirect()->route('backend.farming-batches.show', $farmingBatch)
            ->with('success', 'Đã phê duyệt cho phép thu hoạch lô "' . $farmingBatch->batch_code . '".');
    }
}
