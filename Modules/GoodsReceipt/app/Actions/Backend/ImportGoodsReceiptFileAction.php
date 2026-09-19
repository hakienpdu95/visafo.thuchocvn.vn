<?php

namespace Modules\GoodsReceipt\Actions\Backend;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Exceptions\GoodsReceiptParseException;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\GoodsReceiptItem;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\GoodsReceipt\Support\ImportFileResult;
use Modules\GoodsReceipt\Support\MisaGoodsReceiptParser;
use Modules\GoodsReceipt\Support\ParsedGoodsReceipt;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Product;
use Modules\Vendor\Models\Vendor;
use Throwable;

class ImportGoodsReceiptFileAction
{
    use AsAction;

    public function __construct(private readonly MisaGoodsReceiptParser $parser) {}

    public function handle(UploadedFile $file, ?string $importedById): ImportFileResult
    {
        $originalName = $file->getClientOriginalName();

        try {
            $parsed = $this->parser->parse($file->getRealPath());
        } catch (GoodsReceiptParseException $e) {
            return ImportFileResult::failed($originalName, $e->getMessage());
        }

        if (GoodsReceipt::query()->where('misa_ref_id', $parsed->misaRefId)->exists()) {
            return ImportFileResult::duplicate($originalName, $parsed->misaRefId);
        }

        try {
            ['itemsCount' => $itemsCount, 'newProducts' => $newProducts] =
                DB::transaction(fn () => $this->persist($parsed, $originalName, $importedById));
        } catch (Throwable $e) {
            return ImportFileResult::failed($originalName, $e->getMessage(), $parsed->misaRefId);
        }

        return ImportFileResult::imported($originalName, $parsed->misaRefId, $itemsCount, $newProducts);
    }

    private function persist(ParsedGoodsReceipt $parsed, string $originalName, ?string $importedById): array
    {
        $productIds = [];
        $products = [];
        $newProducts = [];

        foreach ($parsed->items as $item) {
            $product = Product::query()->firstOrCreate(
                ['sku' => $item->sku],
                [
                    'name' => $item->name,
                    'unit' => $item->unit ?? '',
                    'product_type' => ProductType::TradingGood->value,
                    'status' => ProductStatus::Active->value,
                ]
            );

            if ($product->wasRecentlyCreated) {
                $newProducts[] = "{$item->sku} ({$item->name})";
            }

            $productIds[$item->sku] = $product->id;
            $products[$item->sku] = $product;
        }

        $vendorId = $parsed->supplierName !== null
            ? Vendor::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($parsed->supplierName))])
                ->value('id')
            : null;

        $receipt = GoodsReceipt::create([
            'misa_ref_id'      => $parsed->misaRefId,
            'vendor_id'        => $vendorId,
            'supplier_name'    => $parsed->supplierName,
            'receipt_date'     => $parsed->receiptDate,
            'source_file_name' => $originalName,
            'imported_by'      => $importedById,
        ]);

        $batchQtyBySku = [];

        foreach ($parsed->items as $item) {
            GoodsReceiptItem::create([
                'goods_receipt_id' => $receipt->id,
                'product_id'       => $productIds[$item->sku],
                'line_no'          => $item->lineNo,
                'product_name_raw' => $item->name,
                'unit_raw'         => $item->unit,
                'quantity'         => $item->quantity,
            ]);

            $batchQtyBySku[$item->sku] = ($batchQtyBySku[$item->sku] ?? 0) + $item->quantity;
        }

        // File MISA không có NSX → lấy ngày nhập làm gốc để tự tính HSD cho hàng có shelf_life_days.
        $baseDate = $parsed->receiptDate ?? now();

        foreach ($batchQtyBySku as $sku => $totalQty) {
            ProductBatch::create([
                'batch_code'        => $parsed->misaRefId . '-' . $sku,
                'product_id'        => $productIds[$sku],
                'goods_receipt_id'  => $receipt->id,
                'initial_qty'       => $totalQty,
                'current_qty'       => $totalQty,
                'exp_date'          => $products[$sku]->calculateExpDate($baseDate)?->toDateString(),
            ]);
        }

        return ['itemsCount' => count($parsed->items), 'newProducts' => $newProducts];
    }
}
