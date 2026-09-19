<?php

namespace Modules\SalesOrder\Support;

readonly class ParsedSalesOrder
{
    /**
     * @param  ParsedSalesOrderItem[]  $items  Đã gộp nhóm theo Mã số, cộng dồn số lượng yêu cầu
     */
    public function __construct(
        public string $misaRefId,
        public ?string $customerName,
        public ?string $deliveryAddress,
        public array $items,
    ) {}
}
