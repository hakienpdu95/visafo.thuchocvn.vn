<?php

namespace Modules\SalesOrder\Support;

use Illuminate\Support\Carbon;

/**
 * Dữ liệu trang truy xuất công khai — chỉ chứa thông tin an toàn để công bố
 * (không có khách hàng, địa chỉ giao hàng, số phiếu nội bộ).
 */
readonly class TraceabilityData
{
    /**
     * @param  array<int, array{key: string, value: string}>  $attributes
     * @param  array<int, array{title: string, description: string, at: ?Carbon, done: bool}>  $timeline
     * @param  array{name: string, address: string, hotline: string}  $company
     */
    public function __construct(
        public string $traceCode,
        public string $productName,
        public ?string $productImage,
        public ?string $categoryName,
        public string $weight,
        public ?Carbon $mfgDate,
        public ?Carbon $expDate,
        public array $attributes,
        public array $company,
        public string $supplierName,
        public ?string $batchCode,
        public array $timeline,
    ) {}
}
