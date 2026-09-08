<?php

namespace Modules\Warehouse\Actions\Backend;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\RetailItemTag;

class VoidRetailItemTagAction
{
    use AsAction;

    private const VOIDABLE_STATUSES = [
        RetailItemTagStatus::Provisioned,
        RetailItemTagStatus::InStock,
    ];

    public function handle(RetailItemTag $tag): void
    {
        if (! in_array($tag->status, self::VOIDABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'tag' => "Không thể báo hỏng tem đang ở trạng thái \"{$tag->status->label()}\".",
            ]);
        }

        $tag->update(['status' => RetailItemTagStatus::Damaged->value]);
    }
}
