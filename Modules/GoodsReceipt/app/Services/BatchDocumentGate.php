<?php

namespace Modules\GoodsReceipt\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Models\Product;

class BatchDocumentGate
{
    public const QUARANTINE_CATEGORY = 'fresh_meat_seafood';
    public const DATED_CATEGORY      = 'dry_goods_spices';
    public const QUARANTINE_DOCUMENT = 'supplier_vet';

    public const QUARANTINE_MISSING = 'Lô hàng chưa có Phiếu kiểm dịch Thú y. Bắt buộc bổ sung hồ sơ trước khi in tem xuất kho.';
    public const QUARANTINE_NO_BATCH = 'Mặt hàng thuộc nhóm Thịt / Thủy sản: bắt buộc chọn Lô nhập kho đã có Phiếu kiểm dịch Thú y trước khi in tem xuất kho.';
    public const MFG_MISSING = 'Gia vị / Đồ khô bắt buộc ghi NSX để in tem.';
    public const EXP_MISSING = 'Gia vị / Đồ khô bắt buộc ghi HSD để in tem.';

    public static function enforced(): bool
    {
        return (bool) config('trace.document_gate');
    }

    public static function requiresQuarantine(?Product $product): bool
    {
        return $product?->category?->code === self::QUARANTINE_CATEGORY;
    }

    public static function requiresDates(?Product $product): bool
    {
        return $product?->category?->code === self::DATED_CATEGORY;
    }

    public function hasQuarantine(ProductBatch $batch): bool
    {
        return in_array($batch->id, $this->quarantinedBatchIds(collect([$batch])), true);
    }

    public function quarantinedBatchIds(Collection $batches): array
    {
        if ($batches->isEmpty()) {
            return [];
        }

        $batchType = (new ProductBatch())->getMorphClass();
        $receiptType = (new GoodsReceipt())->getMorphClass();
        $receiptIds = $batches->pluck('goods_receipt_id')->filter()->unique()->values();

        $owners = ComplianceDocument::query()
            ->where('status', ComplianceDocumentStatus::Active->value)
            ->where(fn (Builder $q) => $q->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))
            ->whereHas('documentType', fn (Builder $t) => $t->where('code', self::QUARANTINE_DOCUMENT))
            ->whereHas('media')
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $o) => $o->where('documentable_type', $batchType)->whereIn('documentable_id', $batches->pluck('id')))
                ->when($receiptIds->isNotEmpty(), fn (Builder $w) => $w->orWhere(fn (Builder $o) => $o
                    ->where('documentable_type', $receiptType)->whereIn('documentable_id', $receiptIds))))
            ->get(['documentable_type', 'documentable_id']);

        $coveredBatches = $owners->where('documentable_type', $batchType)->pluck('documentable_id')->all();
        $coveredReceipts = $owners->where('documentable_type', $receiptType)->pluck('documentable_id')->all();

        return $batches
            ->filter(fn (ProductBatch $b) => in_array($b->id, $coveredBatches, true) || in_array($b->goods_receipt_id, $coveredReceipts, true))
            ->pluck('id')->values()->all();
    }

    public function assertPrintable(?Product $product, ?string $batchId, ?string $mfgDate, ?string $expDate, ?string $prefix = null): void
    {
        if (! self::enforced()) {
            return;
        }

        $errors = [];
        $label = $prefix !== null && $product !== null ? "Mặt hàng \"{$product->name}\": " : '';

        if (self::requiresQuarantine($product)) {
            $batch = $batchId !== null ? ProductBatch::query()->find($batchId) : null;

            if ($batch === null) {
                $errors['product_batch_id'] = $label . self::QUARANTINE_NO_BATCH;
            } elseif (! $this->hasQuarantine($batch)) {
                $errors['product_batch_id'] = $label . self::QUARANTINE_MISSING;
            }
        }

        if (self::requiresDates($product)) {
            if (blank($mfgDate)) {
                $errors['mfg_date'] = $label . self::MFG_MISSING;
            }
            if (blank($expDate)) {
                $errors['exp_date'] = $label . self::EXP_MISSING;
            }
        }

        if ($errors === []) {
            return;
        }

        throw ValidationException::withMessages($prefix !== null ? [$prefix => array_values($errors)] : $errors);
    }
}
