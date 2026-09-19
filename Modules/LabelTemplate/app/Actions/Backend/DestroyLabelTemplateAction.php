<?php

namespace Modules\LabelTemplate\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LabelTemplate\Models\LabelTemplate;

class DestroyLabelTemplateAction
{
    use AsAction;

    /** @return string Tên mẫu tem vừa xóa (dùng cho thông báo). */
    public function handle(LabelTemplate $labelTemplate): string
    {
        $name = $labelTemplate->name;

        // Xóa mềm không kích hoạt ON DELETE SET NULL → gỡ liên kết ở sản phẩm để chúng quay về tem mặc định.
        DB::transaction(function () use ($labelTemplate) {
            $labelTemplate->products()->update(['label_template_id' => null]);
            $labelTemplate->delete();
        });

        return $name;
    }
}
