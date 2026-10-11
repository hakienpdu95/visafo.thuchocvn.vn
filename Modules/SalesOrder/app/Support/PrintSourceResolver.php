<?php

namespace Modules\SalesOrder\Support;

use Illuminate\Validation\ValidationException;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Enums\PartnerProductStatus;
use Modules\Product\Models\PartnerProduct;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\Vendor\Models\Vendor;

class PrintSourceResolver
{
    public function resolve(array $data, ?SalesOrderItem $item = null): array
    {
        $vendorId = ($data['vendor_id'] ?? null) ?: null;
        $batchId = ($data['product_batch_id'] ?? null) ?: null;

        if ($batchId !== null) {
            $batch = ProductBatch::query()->with('goodsReceipt.vendor')->find($batchId);

            if ($batch === null || ($item !== null && $batch->product_id !== $item->product_id)) {
                throw ValidationException::withMessages(['product_batch_id' => 'Lô nhập kho không khớp với mặt hàng.']);
            }

            $vendorId = $batch->goodsReceipt?->vendor_id ?? $vendorId;
        }

        $vendorSelected = $vendorId !== null;
        $supplierName = trim((string) ($data['supplier_name'] ?? '')) ?: null;
        // Không tự đoán nguồn cung: chỉ lưu đúng lô / NCC / tên nhập tay người dùng thấy trên form; bỏ trống thì lưu NULL (tem chỉ có thông tin sản phẩm).
        $sourceSelected = $vendorSelected || $batchId !== null || $supplierName !== null;

        if ($vendorId !== null) {
            $supplierName = Vendor::query()->whereKey($vendorId)->value('name') ?? $supplierName;
        }

        $resolved = [
            'vendor_id'        => $vendorId,
            'product_batch_id' => $batchId,
            'supplier_name'    => $supplierName,
            'vendor_selected'  => $vendorSelected,
            'source_selected'  => $sourceSelected,
        ];

        // Chỉ form có ô "Hàng hóa NCC" mới gửi khóa này; thiếu khóa = giữ nguyên hàng hóa NCC của tem đã in (xem PrintLog::updateSource).
        if (array_key_exists('partner_product_id', $data)) {
            $resolved['partner_product_id'] = $this->partnerProductId($data['partner_product_id'] ?: null, $vendorId, $item);
        }

        return $resolved;
    }

    /** Hàng hóa NCC phải thuộc đúng NCC đã chọn và ánh xạ đúng sản phẩm chuẩn của dòng hàng. */
    private function partnerProductId(?string $partnerProductId, ?string $vendorId, ?SalesOrderItem $item): ?string
    {
        if ($partnerProductId === null) {
            return null;
        }

        $valid = $vendorId !== null && PartnerProduct::query()
            ->whereKey($partnerProductId)
            ->where('vendor_id', $vendorId)
            ->when($item !== null, fn ($q) => $q->where('product_id', $item->product_id))
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages(['partner_product_id' => 'Hàng hóa nhà cung cấp không khớp với nguồn cung / mặt hàng.']);
        }

        return $partnerProductId;
    }

    /** Hàng hóa NCC ánh xạ (vendor_id, product_id) — đổ vào ô "Hàng hóa nhà cung cấp" của modal in tem. */
    public function partnerProductOptions(SalesOrderItem $item, string $vendorId): array
    {
        if ($item->product_id === null) {
            return [];
        }

        return PartnerProduct::query()
            ->where('vendor_id', $vendorId)
            ->where('product_id', $item->product_id)
            ->where('status', PartnerProductStatus::Active->value)
            ->orderBy('name')->orderBy('id')
            ->get(['id', 'name', 'vendor_sku', 'manufacturer_name'])
            ->map(fn (PartnerProduct $p) => [
                'value' => $p->id,
                'text'  => implode(' - ', array_filter([$p->name, $p->manufacturer_name])) . ($p->vendor_sku ? " ({$p->vendor_sku})" : ''),
            ])
            ->all();
    }

    public function forBulkItem(SalesOrderItem $item, array $itemData, array $globalData): array
    {
        $own = array_filter([
            'vendor_id'        => $itemData['vendor_id'] ?? null,
            'supplier_name'    => trim((string) ($itemData['supplier_name'] ?? '')) ?: null,
            'product_batch_id' => $itemData['product_batch_id'] ?? null,
        ]);
        $partner = array_key_exists('partner_product_id', $itemData) ? ['partner_product_id' => $itemData['partner_product_id']] : [];

        $global = array_filter([
            'vendor_id'     => $globalData['vendor_id'] ?? null,
            'supplier_name' => trim((string) ($globalData['supplier_name'] ?? '')) ?: null,
        ]);

        return $this->resolve(($own !== [] ? $own : $global) + $partner, $item);
    }
}
