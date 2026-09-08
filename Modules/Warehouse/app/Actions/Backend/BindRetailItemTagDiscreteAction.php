<?php

namespace Modules\Warehouse\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\RetailItemTag;
use Modules\Warehouse\Support\TraceabilityLinkBuilder;

class BindRetailItemTagDiscreteAction
{
    use AsAction;

    public function __construct(
        private readonly TraceabilityLinkBuilder $linkBuilder,
    ) {}

    public function handle(Batch $batch, string $scannedValue): RetailItemTag
    {
        $uid = $this->linkBuilder->parseUid($scannedValue) ?? trim($scannedValue);

        return DB::transaction(function () use ($batch, $uid) {
            $tag = RetailItemTag::withoutTenant()->where('uid', $uid)->lockForUpdate()->first();

            if (! $tag) {
                throw ValidationException::withMessages([
                    'scan' => 'Không tìm thấy tem với mã đã quét.',
                ]);
            }

            if ($tag->status !== RetailItemTagStatus::Provisioned) {
                throw ValidationException::withMessages([
                    'scan' => "Tem này đang ở trạng thái \"{$tag->status->label()}\" — không thể gắn kết.",
                ]);
            }

            $nextSerial = ((int) RetailItemTag::withoutTenant()->where('batch_id', $batch->id)->max('serial_number')) + 1;

            $tag->update([
                'product_id'    => $batch->product_id,
                'batch_id'      => $batch->id,
                'serial_number' => $nextSerial,
                'status'        => RetailItemTagStatus::InStock->value,
            ]);

            return $tag;
        });
    }
}
