<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\ApproveFarmingBatchHarvestAction;
use Modules\Product\Actions\Backend\StoreFarmingBatchAction;
use Modules\Product\Data\Requests\StoreFarmingBatchData;
use Modules\Product\Models\AgriFertilizer;
use Modules\Product\Models\AgriPesticide;
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

    public function show(FarmingBatch $farmingBatch)
    {
        $farmingBatch->load(['farmingSource', 'vendor', 'agriSeed', 'partnerProduct', 'logs', 'preHarvestCheckedBy']);

        $pesticideIds = $farmingBatch->logs->pluck('details.agri_pesticide_id')->filter()->unique();
        $fertilizerIds = $farmingBatch->logs->pluck('details.agri_fertilizer_id')->filter()->unique();

        $pesticideNames = AgriPesticide::query()->whereIn('id', $pesticideIds)->pluck('trade_name', 'id');
        $fertilizerNames = AgriFertilizer::query()->whereIn('id', $fertilizerIds)->pluck('name', 'id');

        $pendingQuarantineLogs = $farmingBatch->pendingQuarantineLogs()->get();
        $isReadyForHarvest = $pendingQuarantineLogs->isEmpty();

        return view('product::farming_batches.show', compact(
            'farmingBatch', 'pesticideNames', 'fertilizerNames', 'pendingQuarantineLogs', 'isReadyForHarvest'
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
