<?php

namespace Modules\LabelTemplate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\LabelTemplate\Actions\Backend\DestroyLabelTemplateAction;
use Modules\LabelTemplate\Actions\Backend\StoreLabelTemplateAction;
use Modules\LabelTemplate\Actions\Backend\UpdateLabelTemplateAction;
use Modules\LabelTemplate\Data\Requests\StoreLabelTemplateData;
use Modules\LabelTemplate\Data\Requests\UpdateLabelTemplateData;
use Modules\LabelTemplate\Models\LabelTemplate;
use Modules\LabelTemplate\Queries\ListLabelTemplateSizesHandler;
use Modules\LabelTemplate\Queries\ListLabelTemplateSizesQuery;
use Modules\LabelTemplate\Queries\PreviewLabelTemplateHandler;
use Modules\LabelTemplate\Queries\PreviewLabelTemplateQuery;

class LabelTemplateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(LabelTemplate::class, 'label_template');
    }

    public function index(ListLabelTemplateSizesHandler $sizes)
    {
        return view('labeltemplate::label-templates.index', [
            'sizes' => $sizes->handle(new ListLabelTemplateSizesQuery()),
        ]);
    }

    public function create()
    {
        return view('labeltemplate::label-templates.create');
    }

    public function store(Request $request, StoreLabelTemplateAction $action): RedirectResponse
    {
        $template = $action->handle(StoreLabelTemplateData::validateAndCreate($request->all()));

        return redirect()->route('backend.label-templates.index')
            ->with('success', 'Đã tạo mẫu tem "' . $template->name . '".');
    }

    public function edit(LabelTemplate $labelTemplate)
    {
        return view('labeltemplate::label-templates.edit', ['template' => $labelTemplate]);
    }

    public function update(Request $request, LabelTemplate $labelTemplate, UpdateLabelTemplateAction $action): RedirectResponse
    {
        $action->handle($labelTemplate, UpdateLabelTemplateData::validateAndCreate($request->all()));

        return redirect()->route('backend.label-templates.index')
            ->with('success', 'Đã cập nhật mẫu tem "' . $labelTemplate->name . '".');
    }

    public function destroy(Request $request, LabelTemplate $labelTemplate, DestroyLabelTemplateAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($labelTemplate);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa mẫu tem "' . $name . '".']);
        }

        return redirect()->route('backend.label-templates.index')
            ->with('success', 'Đã xóa mẫu tem "' . $name . '". Sản phẩm đang dùng mẫu này sẽ quay về tem mặc định.');
    }

    /** Xem trước mẫu tem bằng dữ liệu giả — không truy vấn DB thật. */
    public function preview(LabelTemplate $labelTemplate, PreviewLabelTemplateHandler $handler)
    {
        $preview = $handler->handle(new PreviewLabelTemplateQuery($labelTemplate));

        if ($preview->isOk()) {
            return response($preview->html);
        }

        return response()->view('labeltemplate::label-templates.preview-error', [
            'template' => $labelTemplate,
            'title'    => $preview->errorTitle,
            'message'  => $preview->errorMessage,
        ], 422);
    }
}
