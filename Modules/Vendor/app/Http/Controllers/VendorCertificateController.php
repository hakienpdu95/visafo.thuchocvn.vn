<?php

namespace Modules\Vendor\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Vendor\Actions\Backend\DestroyVendorCertificateAction;
use Modules\Vendor\Actions\Backend\StoreVendorCertificateAction;
use Modules\Vendor\Data\Requests\StoreVendorCertificateData;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorCertificate;

class VendorCertificateController extends Controller
{
    private const NULLABLE_FIELDS = ['expiry_date', 'issued_by', 'renewal_deadline'];

    public function store(Request $request, Vendor $vendor, StoreVendorCertificateAction $action): RedirectResponse
    {
        $this->authorize('update', $vendor);

        $input = $request->all();
        foreach (self::NULLABLE_FIELDS as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        $data = StoreVendorCertificateData::validateAndCreate($input);
        $action->handle($vendor, $data);

        return redirect()->route('backend.vendors.show', $vendor)
            ->with('success', 'Đã thêm chứng chỉ mới cho nhà cung cấp.');
    }

    public function destroy(Vendor $vendor, VendorCertificate $certificate, DestroyVendorCertificateAction $action): RedirectResponse
    {
        $this->authorize('update', $vendor);

        $action->handle($certificate);

        return redirect()->route('backend.vendors.show', $vendor)
            ->with('success', 'Đã xóa chứng chỉ.');
    }
}
