<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\DestroyCategoryAction;
use Modules\Product\Actions\Backend\StoreCategoryAction;
use Modules\Product\Actions\Backend\UpdateCategoryAction;
use Modules\Product\Data\Requests\StoreCategoryData;
use Modules\Product\Data\Requests\UpdateCategoryData;
use Modules\Product\Models\Category;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index()
    {
        $statuses = [
            ['value' => 'active', 'text' => 'Đang dùng'],
            ['value' => 'inactive', 'text' => 'Ngừng dùng'],
        ];

        return view('product::categories.index', compact('statuses'));
    }

    public function create()
    {
        return view('product::categories.create');
    }

    public function store(Request $request, StoreCategoryAction $action): RedirectResponse
    {
        $input = $request->all();
        $input['is_active'] = $request->boolean('is_active', true);

        $data     = StoreCategoryData::validateAndCreate($input);
        $category = $action->handle($data);

        return redirect()->route('backend.categories.index')
            ->with('success', 'Đã thêm nhóm hàng "' . $category->name . '".');
    }

    public function edit(Category $category)
    {
        return view('product::categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category, UpdateCategoryAction $action): RedirectResponse
    {
        $input = $request->all();
        $input['is_active'] = $request->boolean('is_active', true);

        if (!$request->user()->can('editCode', Category::class)) {
            $input['code'] = $category->code;
        }

        $data = UpdateCategoryData::validateAndCreate($input);
        $action->handle($category, $data);

        return redirect()->route('backend.categories.index')
            ->with('success', 'Cập nhật nhóm hàng thành công.');
    }

    public function destroy(Category $category, DestroyCategoryAction $action): RedirectResponse
    {
        $action->handle($category);

        return redirect()->route('backend.categories.index')
            ->with('success', 'Đã xóa nhóm hàng.');
    }
}
