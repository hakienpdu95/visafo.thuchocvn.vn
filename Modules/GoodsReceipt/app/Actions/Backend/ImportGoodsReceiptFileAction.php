<?php

namespace Modules\GoodsReceipt\Actions\Backend;

use App\Support\ProductNameMismatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Exceptions\GoodsReceiptParseException;
use Modules\GoodsReceipt\Models\GoodsReceipt;
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

    public function __construct(
        private readonly MisaGoodsReceiptParser $parser,
        private readonly CreateGoodsReceiptAction $createReceipt,
    ) {}

    public function handle(UploadedFile $file, ?string $importedById): ImportFileResult
    {
        $originalName = $file->getClientOriginalName();

        try {
            $parsed = $this->parser->parse($file->getRealPath());
        } catch (GoodsReceiptParseException $e) {
            return ImportFileResult::failed($originalName, $e->getMessage());
        }

        if (GoodsReceipt::withTrashed()->where('misa_ref_id', $parsed->misaRefId)->exists()) {
            return ImportFileResult::duplicate($originalName, $parsed->misaRefId);
        }

        $mismatches = ProductNameMismatch::findInLines(array_map(fn ($item) => ['sku' => $item->sku, 'name' => $item->name], $parsed->items));

        if ($mismatches !== []) {
            return ImportFileResult::failed(
                $originalName,
                'Không import vì có dòng nghi sai mã hàng: ' . implode('; ', $mismatches) . '. Sửa lại mã hàng (hoặc tên hàng) trong file cho khớp danh mục rồi import lại.',
                $parsed->misaRefId,
            );
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
        }

        $vendorId = $parsed->supplierName !== null
            ? Vendor::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($parsed->supplierName))])
                ->value('id')
            : null;

        $this->createReceipt->handle([
            'misa_ref_id'      => $parsed->misaRefId,
            'vendor_id'        => $vendorId,
            'supplier_name'    => $parsed->supplierName,
            'receipt_date'     => $parsed->receiptDate,
            'source_file_name' => $originalName,
            'imported_by'      => $importedById,
        ], array_map(fn ($item) => [
            'product_id'       => $productIds[$item->sku],
            'line_no'          => $item->lineNo,
            'product_name_raw' => $item->name,
            'unit_raw'         => $item->unit,
            'quantity'         => $item->quantity,
        ], $parsed->items));

        return ['itemsCount' => count($parsed->items), 'newProducts' => $newProducts];
    }
}
