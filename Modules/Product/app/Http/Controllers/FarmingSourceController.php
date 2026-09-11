<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\ConfirmFarmingSourcePreSeasonAction;
use Modules\Product\Actions\Backend\StoreFarmingSourceAction;
use Modules\Product\Actions\Backend\UpdateFarmingSourceAction;
use Modules\Product\Data\Requests\FarmingSourceData;
use Modules\Product\Models\FarmingSource;
use Modules\Vendor\Models\Vendor;

class FarmingSourceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(FarmingSource::class, 'farming_source');
    }

    public function index()
    {
        $vendors = Vendor::query()->orderBy('name')->get(['id', 'name']);

        return view('product::farming_sources.index', compact('vendors'));
    }

    public function store(Request $request, StoreFarmingSourceAction $action): RedirectResponse
    {
        $data = FarmingSourceData::validateAndCreate($request->all());
        $farmingSource = $action->handle($data);

        return redirect()->route('backend.farming-sources.index')
            ->with('success', 'Đã thêm vùng trồng "' . $farmingSource->name . '".');
    }

    public function update(Request $request, FarmingSource $farmingSource, UpdateFarmingSourceAction $action): RedirectResponse
    {
        $data = FarmingSourceData::validateAndCreate($request->all());
        $action->handle($farmingSource, $data);

        return redirect()->route('backend.farming-sources.index')
            ->with('success', 'Đã cập nhật vùng trồng "' . $farmingSource->name . '".');
    }

    public function confirmPreSeason(FarmingSource $farmingSource, ConfirmFarmingSourcePreSeasonAction $action): RedirectResponse
    {
        $this->authorize('update', $farmingSource);

        $action->handle($farmingSource, auth()->id());

        return redirect()->route('backend.farming-sources.index')
            ->with('success', 'Đã xác nhận kiểm tra vùng trồng "' . $farmingSource->name . '".');
    }
}
