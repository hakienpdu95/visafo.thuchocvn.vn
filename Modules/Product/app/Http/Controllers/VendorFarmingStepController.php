<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\DestroyVendorFarmingStepAction;
use Modules\Product\Actions\Backend\StoreVendorFarmingStepAction;
use Modules\Product\Actions\Backend\UpdateVendorFarmingStepAction;
use Modules\Product\Data\Requests\VendorFarmingStepData;
use Modules\Product\Models\VendorFarmingStep;
use Modules\Vendor\Models\Vendor;

class VendorFarmingStepController extends Controller
{
    public function store(Request $request, Vendor $vendor, StoreVendorFarmingStepAction $action): RedirectResponse
    {
        $this->authorize('create', VendorFarmingStep::class);

        $data = VendorFarmingStepData::validateAndCreate($request->all());
        $action->handle($vendor, $data);

        return redirect()->route('backend.vendors.show', $vendor)
            ->with('success', 'Đã thêm bước canh tác "' . $data->step_name . '".');
    }

    public function update(Request $request, Vendor $vendor, VendorFarmingStep $farming_step, UpdateVendorFarmingStepAction $action): RedirectResponse
    {
        $this->authorize('update', $farming_step);

        $data = VendorFarmingStepData::validateAndCreate($request->all());
        $action->handle($farming_step, $data);

        return redirect()->route('backend.vendors.show', $vendor)
            ->with('success', 'Đã cập nhật bước canh tác "' . $data->step_name . '".');
    }

    public function destroy(Vendor $vendor, VendorFarmingStep $farming_step, DestroyVendorFarmingStepAction $action): RedirectResponse
    {
        $this->authorize('delete', $farming_step);

        $name = $action->handle($farming_step);

        return redirect()->route('backend.vendors.show', $vendor)
            ->with('success', 'Đã xóa bước canh tác "' . $name . '".');
    }
}
