<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\DestroyPartnerProductAction;
use Modules\Product\Actions\Backend\StorePartnerProductAction;
use Modules\Product\Actions\Backend\UpdatePartnerProductAction;
use Modules\Product\Data\Requests\StorePartnerProductData;
use Modules\Product\Data\Requests\UpdatePartnerProductData;
use Modules\Product\Enums\PartnerProductStatus;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;
use Modules\Product\Queries\GetPartnerProductHandler;
use Modules\Product\Queries\GetPartnerProductQuery;
use Modules\Product\Services\ProductComplianceRuleEngine;
use Modules\Vendor\Models\Vendor;

class PartnerProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PartnerProduct::class, 'partner_product');
    }

    public function index()
    {
        $statuses = collect(PartnerProductStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('product::partner_products.index', compact('statuses'));
    }

    public function create()
    {
        $vendors  = Vendor::query()->orderBy('name')->get(['id', 'name']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('product::partner_products.create', compact('vendors', 'products'));
    }

    public function store(Request $request, StorePartnerProductAction $action): RedirectResponse
    {
        $data           = StorePartnerProductData::validateAndCreate($request->all());
        $partnerProduct = $action->handle($data);

        return redirect()->route('backend.partner-products.show', $partnerProduct)
            ->with('success', 'Hàng hóa "' . $partnerProduct->name . '" của NCC đã được thêm thành công.');
    }

    public function show(PartnerProduct $partnerProduct, GetPartnerProductHandler $handler, ProductComplianceRuleEngine $ruleEngine)
    {
        $partnerProduct = $handler->handle(new GetPartnerProductQuery($partnerProduct));
        $complianceResults = $ruleEngine->evaluate($partnerProduct);

        $missingCodes = collect($ruleEngine->missing($complianceResults))
            ->flatMap(fn ($result) => $result->requirement->documentTypeCodes)
            ->unique()
            ->values();

        $codeToId = DocumentMasterType::query()->pluck('id', 'code');
        $missingTypeIds = $codeToId->only($missingCodes->all())->values();

        $documentTypes = DocumentMasterType::query()
            ->applicableTo('partner_product')
            ->orderBy('name')
            ->get()
            ->sortBy(fn ($type) => $missingTypeIds->contains($type->id) ? 0 : 1)
            ->values();

        return view('product::partner_products.show', compact(
            'partnerProduct', 'complianceResults', 'documentTypes', 'missingTypeIds', 'codeToId'
        ));
    }

    public function edit(PartnerProduct $partnerProduct)
    {
        $vendors  = Vendor::query()->orderBy('name')->get(['id', 'name']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('product::partner_products.edit', compact('partnerProduct', 'vendors', 'products'));
    }

    public function update(Request $request, PartnerProduct $partnerProduct, UpdatePartnerProductAction $action): RedirectResponse
    {
        $data = UpdatePartnerProductData::validateAndCreate($request->all());
        $action->handle($partnerProduct, $data);

        return redirect()->route('backend.partner-products.show', $partnerProduct)
            ->with('success', 'Cập nhật hàng hóa NCC thành công.');
    }

    public function destroy(Request $request, PartnerProduct $partnerProduct, DestroyPartnerProductAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($partnerProduct);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa hàng hóa "' . $name . '".']);
        }

        return redirect()->route('backend.partner-products.index')
            ->with('success', 'Đã xóa hàng hóa "' . $name . '".');
    }
}
