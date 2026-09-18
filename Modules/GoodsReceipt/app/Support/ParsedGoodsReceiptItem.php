<?php

namespace Modules\GoodsReceipt\Support;

readonly class ParsedGoodsReceiptItem
{
    public function __construct(
        public ?int $lineNo,
        public string $name,
        public string $sku,
        public ?string $unit,
        public float $quantity,
    ) {}
}
