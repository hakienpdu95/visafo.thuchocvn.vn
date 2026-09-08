<?php

namespace Modules\Warehouse\Actions\Backend;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\RetailItemTag;

class UnbindRetailItemTagAction
{
    use AsAction;

    private const UNBINDABLE_STATUSES = [
        RetailItemTagStatus::Bound,
        RetailItemTagStatus::InStock,
    ];

    public function handle(RetailItemTag $tag): void
    {
        if (! in_array($tag->status, self::UNBINDABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'tag' => "Không thể gỡ gắn kết tem đang ở trạng thái \"{$tag->status->label()}\". Chỉ gỡ được tem đang \"Chờ lưu hành\" hoặc \"Còn trên kệ\".",
            ]);
        }

        $tag->update([
            'product_id'    => null,
            'batch_id'      => null,
            'serial_number' => null,
            'status'        => RetailItemTagStatus::Provisioned->value,
        ]);
    }
}
