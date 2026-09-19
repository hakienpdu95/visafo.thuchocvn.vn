<?php

namespace Modules\SalesOrder\Support;

readonly class ParsedSalesOrderItem
{
    public function __construct(
        public ?int $lineNo,
        public string $name,
        public string $sku,
        public ?string $unit,
        public float $requestedQty,
    ) {}
}
