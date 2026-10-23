<?php

namespace Modules\GoodsReceipt\Actions\Backend;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\GoodsReceiptItem;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Models\Product;

class UpdateGoodsReceiptAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{product_id: string, line_no: ?int, product_name_raw: ?string, unit_raw: ?string, quantity: float}>  $items
     */
    public function handle(GoodsReceipt $receipt, array $attributes, array $items): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $attributes, $items) {
            $receipt = GoodsReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

            if (! $receipt->isEditable()) {
                throw ValidationException::withMessages([
                    'receipt' => 'Lô của phiếu đã được in tem, kiểm duyệt truy xuất hoặc ghi QC — không thể sửa.',
                ]);
            }

            $productIds = array_column($items, 'product_id');
            $batches = ProductBatch::withTrashed()->where('goods_receipt_id', $receipt->id)->get()->keyBy('product_id');
            $removed = $batches->reject(fn (ProductBatch $b) => in_array($b->product_id, $productIds, true));

            self::guardRemovable($removed->reject->trashed());

            $oldDate = $receipt->receipt_date?->toDateString();
            $vendorChanged = $receipt->vendor_id !== ($attributes['vendor_id'] ?? null);

            $receipt->update($attributes);

            $newDate = $receipt->receipt_date?->toDateString();
            $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');
            $existingItems = $receipt->items()->orderBy('line_no')->get()->toBase()->groupBy('product_id');

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $product = $products->get($productId);
                $group = $existingItems->get($productId);

                if ($group) {
                    $group->first()->update(['line_no' => $item['line_no'], 'quantity' => $item['quantity']]);
                    $group->slice(1)->each->forceDelete();
                } else {
                    GoodsReceiptItem::create([...$item, 'goods_receipt_id' => $receipt->id]);
                }

                $batch = $batches->get($productId);

                if (! $batch) {
                    ProductBatch::create([
                        'batch_code'       => CreateGoodsReceiptAction::batchCode($receipt->misa_ref_id, $product),
                        'product_id'       => $productId,
                        'goods_receipt_id' => $receipt->id,
                        'initial_qty'      => $item['quantity'],
                        'current_qty'      => $item['quantity'],
                        'exp_date'         => CreateGoodsReceiptAction::defaultExpDate($product, $newDate),
                    ]);

                    continue;
                }

                $currentQty = $batch->trashed()
                    ? $item['quantity']
                    : max(0, (float) $batch->current_qty + $item['quantity'] - (float) $batch->initial_qty);

                if ($batch->trashed()) {
                    $batch->restore();
                }

                $batch->fill([
                    'batch_code'  => CreateGoodsReceiptAction::batchCode($receipt->misa_ref_id, $product),
                    'initial_qty' => $item['quantity'],
                    'current_qty' => $currentQty,
                ]);

                if ($vendorChanged) {
                    $batch->farming_batch_id = null;
                }

                if ($oldDate !== $newDate && $batch->mfg_date === null
                    && $batch->exp_date?->toDateString() === CreateGoodsReceiptAction::defaultExpDate($product, $oldDate)) {
                    $batch->exp_date = CreateGoodsReceiptAction::defaultExpDate($product, $newDate);
                }

                $batch->save();
            }

            $existingItems->except($productIds)->flatten(1)->each->forceDelete();
            $removed->each->forceDelete();

            return $receipt;
        });
    }

    /** @param Collection<int, ProductBatch> $batches */
    public static function guardRemovable(Collection $batches): void
    {
        $blocked = $batches->filter(fn (ProductBatch $b) => $b->extraAttributes()->exists() || $b->documents()->exists());

        if ($blocked->isEmpty()) {
            return;
        }

        $codes = $blocked->map(fn (ProductBatch $b) => $b->batch_code)->implode(', ');

        throw ValidationException::withMessages([
            'items' => 'Lô ' . $codes . ' đã có thông tin bổ sung hoặc hồ sơ — xóa các dữ liệu đó ở trang chi tiết phiếu trước.',
        ]);
    }
}
