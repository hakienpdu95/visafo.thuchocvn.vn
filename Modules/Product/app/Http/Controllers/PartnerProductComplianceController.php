<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\DestroyPartnerProductComplianceAction;
use Modules\Product\Actions\Backend\StorePartnerProductComplianceAction;
use Modules\Product\Data\Requests\StorePartnerProductComplianceData;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\PartnerProductCompliance;

class PartnerProductComplianceController extends Controller
{
    private const NULLABLE_FIELDS = ['issue_date', 'expiration_date', 'file_url'];

    public function store(Request $request, PartnerProduct $partnerProduct, StorePartnerProductComplianceAction $action): RedirectResponse
    {
        $this->authorize('create', PartnerProductCompliance::class);

        $input = $request->all();
        foreach (self::NULLABLE_FIELDS as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        $data = StorePartnerProductComplianceData::validateAndCreate($input);
        $action->handle($partnerProduct, $data);

        return redirect()->route('backend.partner-products.show', $partnerProduct)
            ->with('success', 'Đã thêm hồ sơ chất lượng mới cho hàng hóa NCC.');
    }

    public function destroy(PartnerProduct $partnerProduct, PartnerProductCompliance $compliance, DestroyPartnerProductComplianceAction $action): RedirectResponse
    {
        $this->authorize('delete', $compliance);

        $action->handle($compliance);

        return redirect()->route('backend.partner-products.show', $partnerProduct)
            ->with('success', 'Đã xóa hồ sơ chất lượng.');
    }
}
