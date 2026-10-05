<?php

namespace Modules\SalesOrder\Support;

use Illuminate\Support\Carbon;
use Modules\SalesOrder\Enums\PrintLogStatus;

/**
 * Dữ liệu trang truy xuất công khai — chỉ chứa thông tin an toàn để công bố
 * (không có khách hàng, địa chỉ giao hàng, số phiếu nội bộ).
 *
 * Các trường tối thiểu theo Thông tư 02/2024/TT-BKHCN:
 *  tên SP (productName) · hình ảnh (productImages) · đơn vị SXKD + địa chỉ (producer, company) · công đoạn (journey)
 *  · mã truy vết SP (traceCode, batchCode) · thời gian SX (mfgDate) · mã truy vết địa điểm (location)
 *  · thương hiệu/mã số (brand, productSku) · thời hạn sử dụng (expDate) · tiêu chuẩn áp dụng (standards).
 */
readonly class TraceabilityData
{
    /**
     * @param  string[]  $productImages  ảnh slider, ảnh chính luôn ở index 0
     * @param  array<int, array{key: string, value: string}>  $attributes
     * @param  array{name: string, address: string, hotline: string, taxCode: string, area: string}  $company  đơn vị đóng gói / phân phối (tên pháp nhân chủ hệ thống)
     * @param  array{name: string, address: ?string, taxCode: ?string, isVendor: bool, isOwnFarm: bool}  $producer  đơn vị sản xuất / nguồn cung (isOwnFarm = vùng trồng tự quản của doanh nghiệp)
     * @param  array{code: string, name: ?string, address: ?string, area: ?float, waterSource: ?string, harvestedAt: ?Carbon, batchCode: string, vendorName: ?string, isOwn: bool, preSeason: ?bool, harvestApprovedAt: ?Carbon}|null  $location  vùng trồng của lô canh tác gắn với lô nhập (null = lô nhập chưa liên kết lô canh tác)
     * @param  array<int, array{label: string, documents: array<int, array{name: string, number: ?string, issuedBy: ?string, issuedAt: ?Carbon, expiresAt: ?Carbon, files: array<int, array{url: string, thumb: string, preview: string, isPdf: bool}>}>}>  $documentGroups  hồ sơ doanh nghiệp công khai theo nhóm (tab "Thương hiệu")
     * @param  array<int, array{label: string, result: ?string, at: ?Carbon}>  $qualityChecks  3 khâu QC của doanh nghiệp (result null = chưa ghi nhận)
     * @param  ?string  $qcConclusion  kết luận lô: pass = đủ điều kiện xuất, fail = không đạt, null = chưa kết luận
     * @param  array{code: string, status: string, shippedAt: Carbon, deliveredAt: ?Carbon, recipient: ?string, area: ?string, warehouse: ?string}|null  $delivery  giao vận (null = chưa xuất kho); recipient đã che tên, area chỉ phường/quận + tỉnh
     */
    public function __construct(
        public string $traceCode,
        public string $productName,
        public string $productDescription,
        public array $productImages,
        public ?string $categoryName,
        public ?string $productSku,
        public string $brand, // thương hiệu ngắn (VISAFO) — khác tên pháp nhân company['name']
        public string $weight,
        public ?Carbon $mfgDate,
        public ?Carbon $expDate,
        public ?Carbon $packedAt, // thời điểm in tem = đóng gói
        public ?string $batchQuantity, // "120 kg" — khối lượng lô nhập, hoặc của dòng đơn khi tem không gắn lô
        public array $attributes,
        public array $company,
        public array $producer,
        public ?array $location,
        public ?string $batchCode,
        public array $standards,
        public PrintLogStatus $status = PrintLogStatus::Active,
        public ?string $statusReason = null,
        public ?array $supplier = null,
        public array $relatedProducts = [],
        public string $brandStory = '',
        public array $documentGroups = [],
        public array $qualityChecks = [],
        public ?string $qcConclusion = null,
        public ?array $delivery = null,
        /** @var array<int, array{title: string, time: ?string, meta: ?string, done: bool, ok: ?bool}> khối "Hành trình hàng hóa" (5 mốc tóm tắt) */
        public array $journey = [],
    ) {}
}
