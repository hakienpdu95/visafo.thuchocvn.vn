<?php

namespace Modules\GoodsReceipt\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\GoodsReceiptItem;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Models\Product;

class CreateGoodsReceiptAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{product_id: string, line_no: ?int, product_name_raw: ?string, unit_raw: ?string, quantity: float}>  $items
     */
    public function handle(array $attributes, array $items): GoodsReceipt
    {
        return DB::transaction(function () use ($attributes, $items) {
            $receipt = GoodsReceipt::create($attributes);
            $qtyByProduct = [];

            foreach ($items as $item) {
                GoodsReceiptItem::create([...$item, 'goods_receipt_id' => $receipt->id]);
                $qtyByProduct[$item['product_id']] = ($qtyByProduct[$item['product_id']] ?? 0) + $item['quantity'];
            }

            $products = Product::query()->whereIn('id', array_keys($qtyByProduct))->get()->keyBy('id');

            foreach ($qtyByProduct as $productId => $qty) {
                $product = $products->get($productId);

                ProductBatch::create([
                    'batch_code'       => self::batchCode($receipt->misa_ref_id, $product),
                    'product_id'       => $productId,
                    'goods_receipt_id' => $receipt->id,
                    'initial_qty'      => $qty,
                    'current_qty'      => $qty,
                    'exp_date'         => self::defaultExpDate($product, $receipt->receipt_date),
                ]);
            }

            return $receipt;
        });
    }

    public static function batchCode(string $refId, Product $product): string
    {
        return $refId . '-' . $product->sku;
    }

    public static function defaultExpDate(Product $product, mixed $receiptDate): ?string
    {
        return $product->calculateExpDate($receiptDate ?? now())?->toDateString();
    }
}
