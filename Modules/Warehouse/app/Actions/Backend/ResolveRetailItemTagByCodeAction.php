<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Models\RetailItemTag;
use Modules\Warehouse\Support\TraceabilityLinkBuilder;

class ResolveRetailItemTagByCodeAction
{
    use AsAction;

    public function __construct(
        private readonly TraceabilityLinkBuilder $linkBuilder,
    ) {}

    /** Tìm tem theo bất kỳ mã nào nhân viên có trong tay: URL quét được, uid, gs1_serial, hoặc mã QR định dạng cũ. */
    public function handle(string $code): ?RetailItemTag
    {
        $code = trim($code);

        $uid = $this->linkBuilder->parseUid($code) ?? $code;

        $tag = RetailItemTag::with(['product', 'batch.vendor', 'externalOrder.customer'])
            ->where('uid', $uid)
            ->first();

        if ($tag) {
            return $tag;
        }

        $tag = RetailItemTag::with(['product', 'batch.vendor', 'externalOrder.customer'])
            ->where('gs1_serial', $code)
            ->first();

        if ($tag) {
            return $tag;
        }

        $parsed = $this->linkBuilder->parse($code);

        if (! $parsed) {
            return null;
        }

        return RetailItemTag::with(['product', 'batch.vendor', 'externalOrder.customer'])
            ->whereHas('product', fn ($q) => $q->where('sku', $parsed['sku']))
            ->whereHas('batch', fn ($q) => $q->where('internal_batch_code', $parsed['batch_code']))
            ->where('serial_number', $parsed['serial'])
            ->first();
    }
}
