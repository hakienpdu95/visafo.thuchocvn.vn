<?php

namespace Modules\SalesOrder\Support;

use Illuminate\Support\Carbon;
use Modules\SalesOrder\Enums\PrintLogStatus;

/**
 * Dữ liệu trang truy xuất công khai — chỉ chứa thông tin an toàn để công bố
 * (không có khách hàng, địa chỉ giao hàng, số phiếu nội bộ).
 *
 * Các trường tối thiểu theo Thông tư 02/2024/TT-BKHCN:
 *  tên SP (productName) · hình ảnh (productImages) · đơn vị SXKD + địa chỉ (producer, company) · công đoạn (timeline)
 *  · mã truy vết SP (traceCode, batchCode) · thời gian SX (mfgDate) · mã truy vết địa điểm (location)
 *  · thương hiệu/mã số (brand, productSku) · thời hạn sử dụng (expDate) · tiêu chuẩn áp dụng (standards).
 */
readonly class TraceabilityData
{
    /**
     * @param  string[]  $productImages  ảnh slider, ảnh chính luôn ở index 0
     * @param  array<int, array{key: string, value: string}>  $attributes
     * @param  array{name: string, address: string, hotline: string}  $company  đơn vị đóng gói / phân phối (tên pháp nhân chủ hệ thống)
     * @param  array{name: string, address: ?string, taxCode: ?string, isVendor: bool}  $producer  đơn vị sản xuất / nguồn cung
     * @param  array{code: string, name: ?string, address: ?string}|null  $location  mã vùng trồng / cơ sở sản xuất
     * @param  array<int, array{icon: string, title: string, description: string, at: ?Carbon, done: bool}>  $timeline
     * @param  array<int, array{name: string, number: ?string, issuedBy: ?string, expiresAt: ?Carbon, owner: string}>  $standards
     * @param  array{name: string, address: ?string}|null  $supplier  nhà cung cấp của lô nhập (null khi không xác định được)
     * @param  array<int, array{name: string, image: ?string, isCurrent: bool}>  $relatedProducts  SP cùng đơn bán, cùng nguồn cung; SP đang xem ở đầu
     * @param  array<int, array{name: string, number: ?string, issuedBy: ?string, issuedAt: ?Carbon, expiresAt: ?Carbon, files: array<int, array{url: string, thumb: string, preview: string, isPdf: bool}>}>  $companyDocuments  hồ sơ doanh nghiệp công khai (tab "Thương hiệu")
     */
    public function __construct(
        public string $traceCode,
        public string $productName,
        public string $productSubtitle,
        public string $productDescription,
        public array $productImages,
        public ?string $categoryName,
        public ?string $productSku,
        public string $brand, // thương hiệu ngắn (VISAFO) — khác tên pháp nhân company['name']
        public string $weight,
        public ?Carbon $mfgDate,
        public ?Carbon $expDate,
        public array $attributes,
        public array $company,
        public array $producer,
        public ?array $location,
        public ?string $batchCode,
        public array $timeline,
        public array $standards,
        public PrintLogStatus $status = PrintLogStatus::Active,
        public ?string $statusReason = null,
        public ?array $supplier = null,
        public array $relatedProducts = [],
        public string $brandStory = '',
        public array $companyDocuments = [],
    ) {}
}
