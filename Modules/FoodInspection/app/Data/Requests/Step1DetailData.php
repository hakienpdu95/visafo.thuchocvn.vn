<?php

namespace Modules\FoodInspection\Data\Requests;

use Modules\FoodInspection\Enums\FoodGroup;
use Modules\FoodInspection\Enums\InspectionResult;
use Modules\FoodInspection\Enums\QuickTestResult;
use Modules\FoodInspection\Enums\StorageCondition;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Một dòng hàng trong Sổ kiểm thực Bước 1. Nhóm A (tươi sống) và Nhóm B (khô/bao gói) dùng chung cấu trúc này. */
class Step1DetailData extends Data
{
    public function __construct(
        public readonly FoodGroup $food_group,

        #[Required, StringType, Max(255)]
        public readonly string $product_name,

        #[Required, StringType, Max(255)]
        public readonly string $vendor_name,

        #[Required, StringType, Max(255)]
        public readonly string $deliverer_name,

        // Địa chỉ / SĐT nơi cung cấp: tự điền từ Master Data khi chọn NCC, nhập tay khi NCC mới.
        #[Nullable, StringType, Max(500)]
        public readonly ?string $supplier_address = null,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $supplier_phone = null,

        #[Required, Date]
        public readonly string $received_at,

        public readonly InspectionResult $sensory_result,

        #[Nullable, StringType, Exists('vendors', 'id')]
        public readonly ?string $vendor_id = null,

        // Có khi sửa sổ: id dòng hàng hiện có (để cập nhật); trống = dòng mới.
        #[Nullable, StringType, Exists('food_inspection_step1_details', 'id')]
        public readonly ?string $id = null,

        #[Nullable, StringType, Exists('products', 'id')]
        public readonly ?string $product_id = null,

        #[Nullable, Min(0), Max(99999999)]
        public readonly ?float $quantity = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $unit = null,

        public readonly bool $has_invoice = true,

        public readonly bool $has_vet_cert = true,

        public readonly bool $has_quarantine_cert = true,

        public readonly QuickTestResult $quick_test_result = QuickTestResult::None,

        #[Nullable, StringType, Max(1000)]
        public readonly ?string $handling_measure = null,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $manufacturer_name = null,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $manufacturer_address = null,

        #[Nullable, Date]
        public readonly ?string $expiry_date = null,

        public readonly ?StorageCondition $storage_condition = null,
    ) {}

    /** Cảm quan hoặc test nhanh "Không đạt" → bắt buộc ghi biện pháp xử lý. */
    public function isFailed(): bool
    {
        return $this->sensory_result === InspectionResult::Fail || $this->quick_test_result === QuickTestResult::Fail;
    }
}
