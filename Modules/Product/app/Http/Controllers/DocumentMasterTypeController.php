<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\DestroyDocumentMasterTypeAction;
use Modules\Product\Actions\Backend\StoreDocumentMasterTypeAction;
use Modules\Product\Actions\Backend\UpdateDocumentMasterTypeAction;
use Modules\Product\Data\Requests\StoreDocumentMasterTypeData;
use Modules\Product\Data\Requests\UpdateDocumentMasterTypeData;
use Modules\Product\Enums\ProductCategoryType;
use Modules\Product\Models\DocumentMasterType;

class DocumentMasterTypeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DocumentMasterType::class, 'document_master_type');
    }

    public function index()
    {
        $categoryTypes = collect(ProductCategoryType::cases())
            ->map(fn ($c) => ['value' => $c->value, 'text' => $c->label()])
            ->all();

        return view('product::document_master_types.index', compact('categoryTypes'));
    }

    public function create()
    {
        $categoryTypes = ProductCategoryType::cases();

        return view('product::document_master_types.create', compact('categoryTypes'));
    }

    public function store(Request $request, StoreDocumentMasterTypeAction $action): RedirectResponse
    {
        $input = $request->all();
        $input['is_required_issue_date']  = $request->boolean('is_required_issue_date');
        $input['is_required_expiry_date'] = $request->boolean('is_required_expiry_date');

        $data = StoreDocumentMasterTypeData::validateAndCreate($input);
        $action->handle($data);

        return redirect()->route('backend.document-master-types.index')
            ->with('success', 'Đã thêm loại giấy tờ mới.');
    }

    public function edit(DocumentMasterType $documentMasterType)
    {
        $categoryTypes = ProductCategoryType::cases();

        return view('product::document_master_types.edit', compact('documentMasterType', 'categoryTypes'));
    }

    public function update(Request $request, DocumentMasterType $documentMasterType, UpdateDocumentMasterTypeAction $action): RedirectResponse
    {
        $input = $request->all();
        $input['is_required_issue_date']  = $request->boolean('is_required_issue_date');
        $input['is_required_expiry_date'] = $request->boolean('is_required_expiry_date');

        $data = UpdateDocumentMasterTypeData::validateAndCreate($input);
        $action->handle($documentMasterType, $data);

        return redirect()->route('backend.document-master-types.index')
            ->with('success', 'Cập nhật loại giấy tờ thành công.');
    }

    public function destroy(DocumentMasterType $documentMasterType, DestroyDocumentMasterTypeAction $action): RedirectResponse
    {
        $action->handle($documentMasterType);

        return redirect()->route('backend.document-master-types.index')
            ->with('success', 'Đã xóa loại giấy tờ.');
    }
}
