<?php

namespace Modules\FoodInspection\Support;

use Modules\FoodInspection\Data\Requests\Step1DetailData;
use Modules\FoodInspection\Enums\FoodGroup;
use Modules\FoodInspection\Enums\QuickTestResult;

class Step1RowAttributes
{
    /** Nhóm B không có hồ sơ thú y/kiểm dịch/test nhanh (chỉ có Chứng từ); Nhóm A không có thông tin nhà sản xuất/HSD/bảo quản. */
    public static function from(Step1DetailData $d, int $lineNo, ?string $vendorId): array
    {
        $isFresh = $d->food_group === FoodGroup::Fresh;

        return [
            'product_id'            => $d->product_id,
            'vendor_id'             => $vendorId,
            'vendor_name'           => $d->vendor_name,
            'supplier_address'      => $d->supplier_address,
            'supplier_phone'        => $d->supplier_phone,
            // Chuỗi chuẩn Mẫu số 1 "[Địa chỉ] - [SĐT]" cho bản in PDF/Excel.
            'supplier_address_phone' => collect([$d->supplier_address, $d->supplier_phone])->filter(fn ($x) => filled($x))->implode(' - ') ?: null,
            'deliverer_name'        => $d->deliverer_name,
            'received_at'           => $d->received_at,
            'line_no'               => $lineNo,
            'food_group'            => $d->food_group,
            'product_name'          => $d->product_name,
            'quantity'              => $d->quantity,
            'unit'                  => $d->unit,
            'has_invoice'           => $d->has_invoice,
            'has_vet_cert'          => $isFresh ? $d->has_vet_cert : null,
            'has_quarantine_cert'   => $isFresh ? $d->has_quarantine_cert : null,
            'sensory_result'        => $d->sensory_result,
            'quick_test_result'     => $isFresh ? $d->quick_test_result : QuickTestResult::None,
            'handling_measure'      => $d->isFailed() ? $d->handling_measure : null,
            'manufacturer_name'     => $isFresh ? null : $d->manufacturer_name,
            'manufacturer_address'  => $isFresh ? null : $d->manufacturer_address,
            'expiry_date'           => $isFresh ? null : $d->expiry_date,
            'storage_condition'     => $isFresh ? null : $d->storage_condition,
        ];
    }
}
