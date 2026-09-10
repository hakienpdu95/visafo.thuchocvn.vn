<?php

namespace Modules\Compliance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Compliance\Actions\Backend\DestroyComplianceDocumentAction;
use Modules\Compliance\Actions\Backend\StoreComplianceDocumentAction;
use Modules\Compliance\Data\Requests\StoreComplianceDocumentData;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;
use Modules\Vendor\Models\Vendor;

class ComplianceDocumentController extends Controller
{
    private const DOCUMENTABLE_LABELS = [
        'vendor'           => 'Nhà cung cấp',
        'product'          => 'Sản phẩm Visafo',
        'partner_product'  => 'Hàng hóa NCC',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ComplianceDocument::class);

        $documentableTypes = collect(self::DOCUMENTABLE_LABELS)
            ->map(fn ($label, $value) => ['value' => $value, 'text' => $label])
            ->values()
            ->all();

        return view('compliance::documents.index', [
            'documentableTypes' => $documentableTypes,
        ]);
    }

    public function storeForVendor(Request $request, Vendor $vendor, StoreComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $vendor);

        return $this->store($request, $vendor, $action, 'backend.vendors.show');
    }

    public function destroyForVendor(Vendor $vendor, ComplianceDocument $document, DestroyComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $vendor);

        return $this->destroy($document, $action, 'backend.vendors.show', $vendor);
    }

    public function storeForProduct(Request $request, Product $product, StoreComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $product);

        return $this->store($request, $product, $action, 'backend.products.edit');
    }

    public function destroyForProduct(Product $product, ComplianceDocument $document, DestroyComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $product);

        return $this->destroy($document, $action, 'backend.products.edit', $product);
    }

    public function storeForPartnerProduct(Request $request, PartnerProduct $partnerProduct, StoreComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $partnerProduct);

        return $this->store($request, $partnerProduct, $action, 'backend.partner-products.show');
    }

    public function destroyForPartnerProduct(PartnerProduct $partnerProduct, ComplianceDocument $document, DestroyComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $partnerProduct);

        return $this->destroy($document, $action, 'backend.partner-products.show', $partnerProduct);
    }

    private function store(Request $request, Model $documentable, StoreComplianceDocumentAction $action, string $redirectRoute): RedirectResponse
    {
        $data = StoreComplianceDocumentData::validateAndCreate($request->all());
        $action->handle($documentable, $data);

        return redirect()->route($redirectRoute, $documentable)
            ->with('success', 'Đã thêm hồ sơ mới.');
    }

    private function destroy(ComplianceDocument $document, DestroyComplianceDocumentAction $action, string $redirectRoute, Model $documentable): RedirectResponse
    {
        $action->handle($document);

        return redirect()->route($redirectRoute, $documentable)
            ->with('success', 'Đã xóa hồ sơ.');
    }
}
