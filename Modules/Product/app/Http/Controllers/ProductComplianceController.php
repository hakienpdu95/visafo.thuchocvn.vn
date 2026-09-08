<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\DestroyProductComplianceAction;
use Modules\Product\Actions\Backend\StoreProductComplianceAction;
use Modules\Product\Data\Requests\StoreProductComplianceData;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCompliance;

class ProductComplianceController extends Controller
{
    private const NULLABLE_FIELDS = ['classification_grade', 'issue_date', 'expiration_date', 'file_url', 'pif_file_url'];

    public function store(Request $request, Product $product, StoreProductComplianceAction $action): RedirectResponse
    {
        $this->authorize('create', ProductCompliance::class);

        $input = $request->all();
        foreach (self::NULLABLE_FIELDS as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        $data = StoreProductComplianceData::validateAndCreate($input);
        $action->handle($product, $data);

        return redirect()->route('backend.products.show', $product)
            ->with('success', 'Đã thêm hồ sơ pháp lý mới cho sản phẩm.');
    }

    public function destroy(Product $product, ProductCompliance $compliance, DestroyProductComplianceAction $action): RedirectResponse
    {
        $this->authorize('delete', $compliance);

        $action->handle($compliance);

        return redirect()->route('backend.products.show', $product)
            ->with('success', 'Đã xóa hồ sơ pháp lý.');
    }
}
