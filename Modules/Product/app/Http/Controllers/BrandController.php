<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\DestroyBrandAction;
use Modules\Product\Actions\Backend\StoreBrandAction;
use Modules\Product\Actions\Backend\UpdateBrandAction;
use Modules\Product\Data\Requests\StoreBrandData;
use Modules\Product\Data\Requests\UpdateBrandData;
use Modules\Product\Models\Brand;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Brand::class, 'brand');
    }

    public function index()
    {
        return view('product::brands.index');
    }

    public function create()
    {
        return view('product::brands.create');
    }

    public function store(Request $request, StoreBrandAction $action): RedirectResponse
    {
        $data  = StoreBrandData::validateAndCreate($request->all());
        $brand = $action->handle($data, $request->input('logo_media_uuid'));

        return redirect()->route('backend.brands.index')
            ->with('success', 'Đã thêm thương hiệu "' . $brand->name . '".');
    }

    public function edit(Brand $brand)
    {
        return view('product::brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand, UpdateBrandAction $action): RedirectResponse
    {
        $data = UpdateBrandData::validateAndCreate($request->all());
        $action->handle($brand, $data);

        return redirect()->route('backend.brands.index')
            ->with('success', 'Cập nhật thương hiệu thành công.');
    }

    public function destroy(Brand $brand, DestroyBrandAction $action): RedirectResponse
    {
        $action->handle($brand);

        return redirect()->route('backend.brands.index')
            ->with('success', 'Đã xóa thương hiệu.');
    }
}
