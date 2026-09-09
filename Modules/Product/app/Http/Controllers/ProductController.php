<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\DestroyProductAction;
use Modules\Product\Actions\Backend\StoreProductAction;
use Modules\Product\Actions\Backend\UpdateProductAction;
use Modules\Product\Data\Requests\StoreProductData;
use Modules\Product\Data\Requests\UpdateProductData;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Queries\GetProductHandler;
use Modules\Product\Queries\GetProductQuery;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index()
    {
        $categories = Category::orderBy('name')->get(['id', 'name'])
            ->map(fn ($c) => ['value' => $c->id, 'text' => $c->name])
            ->all();

        $productTypes = collect(ProductType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $statuses = collect(ProductStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('product::products.index', compact('categories', 'productTypes', 'statuses'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('product::products.create', compact('categories'));
    }

    public function store(Request $request, StoreProductAction $action): RedirectResponse
    {
        $data    = StoreProductData::validateAndCreate($request->all());
        $product = $action->handle($data);

        return redirect()->route('backend.products.show', $product)
            ->with('success', 'Sản phẩm "' . $product->name . '" đã được tạo thành công.');
    }

    public function show(Product $product, GetProductHandler $handler)
    {
        $product = $handler->handle(new GetProductQuery($product));

        $groupOrder = collect(\Modules\Product\Enums\DocumentGroupType::cases())
            ->map(fn ($g) => "'{$g->value}'")
            ->implode(',');

        $documentTypes = \Modules\Product\Models\DocumentMasterType::query()
            ->orderByRaw("FIELD(document_group, $groupOrder)")
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($type) => $type->document_group?->label() ?? 'Chưa phân nhóm');

        return view('product::products.show', compact('product', 'documentTypes'));
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();

        return view('product::products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        $data = UpdateProductData::validateAndCreate($request->all());
        $action->handle($product, $data);

        return redirect()->route('backend.products.show', $product)
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(Request $request, Product $product, DestroyProductAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($product);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa sản phẩm "' . $name . '".']);
        }

        return redirect()->route('backend.products.index')
            ->with('success', 'Đã xóa sản phẩm "' . $name . '".');
    }
}
