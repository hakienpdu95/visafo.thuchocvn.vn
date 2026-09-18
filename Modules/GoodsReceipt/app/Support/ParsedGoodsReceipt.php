<?php

namespace Modules\GoodsReceipt\Support;

use Illuminate\Support\Carbon;

readonly class ParsedGoodsReceipt
{
    /**
     * @param  ParsedGoodsReceiptItem[]  $items
     */
    public function __construct(
        public string $misaRefId,
        public ?string $supplierName,
        public ?Carbon $receiptDate,
        public array $items,
    ) {}
}
