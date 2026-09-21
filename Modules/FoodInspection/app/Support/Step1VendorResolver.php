<?php

namespace Modules\FoodInspection\Support;

use Illuminate\Support\Collection;
use Modules\FoodInspection\Data\Requests\Step1DetailData;
use Modules\Vendor\Models\Vendor;

/** Gắn `vendor_id` cho từng dòng hàng; NCC gõ tay (chưa có vendor_id) được tìm theo tên hoặc tạo mới trong Master Data. */
class Step1VendorResolver
{
    /**
     * @param  Collection<int, Step1DetailData>  $details
     * @return array<int, string>  vendor_id theo chỉ số dòng
     */
    public function resolve(Collection $details): array
    {
        $created = []; // cùng một tên NCC mới ở nhiều dòng chỉ tạo 1 bản ghi
        $ids = [];

        foreach ($details as $i => $detail) {
            if ($detail->vendor_id !== null) {
                $ids[$i] = $detail->vendor_id;
                continue;
            }

            $key = mb_strtolower(trim($detail->vendor_name));
            $created[$key] ??= $this->findOrCreate($detail);
            $ids[$i] = $created[$key];
        }

        return $ids;
    }

    private function findOrCreate(Step1DetailData $d): string
    {
        $name = trim($d->vendor_name);

        // NCC đã có trong Master Data (kể cả đang ngừng hợp tác nên không nằm trong gợi ý) → dùng lại, không ghi đè thông tin.
        $vendor = Vendor::query()->where('name', $name)->first()
            ?? Vendor::query()->create([
                'name'         => $name,
                'address'      => $d->supplier_address,
                'phone_number' => $d->supplier_phone,
            ]);

        return $vendor->id;
    }
}
