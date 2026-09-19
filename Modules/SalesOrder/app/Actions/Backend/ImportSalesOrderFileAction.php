<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Product;
use Modules\SalesOrder\Exceptions\SalesOrderParseException;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\ImportFileResult;
use Modules\SalesOrder\Support\MisaSalesOrderParser;
use Modules\SalesOrder\Support\ParsedSalesOrder;
use Throwable;

class ImportSalesOrderFileAction
{
    use AsAction;

    public function __construct(private readonly MisaSalesOrderParser $parser) {}

    public function handle(UploadedFile $file, ?string $importedById): ImportFileResult
    {
        $originalName = $file->getClientOriginalName();

        try {
            $parsed = $this->parser->parse($file->getRealPath());
        } catch (SalesOrderParseException $e) {
            return ImportFileResult::failed($originalName, $e->getMessage());
        } catch (Throwable $e) {
            return ImportFileResult::failed($originalName, 'Không đọc được file Excel: ' . $e->getMessage());
        }

        // withTrashed: unique index trên misa_ref_id vẫn chặn cả phiếu đã xóa mềm.
        if (SalesOrder::withTrashed()->where('misa_ref_id', $parsed->misaRefId)->exists()) {
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

    private function persist(ParsedSalesOrder $parsed, string $originalName, ?string $importedById): array
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

        $order = SalesOrder::create([
            'misa_ref_id'      => $parsed->misaRefId,
            'customer_name'    => $parsed->customerName,
            'delivery_address' => $parsed->deliveryAddress,
            'status'           => SalesOrder::STATUS_PENDING,
            'source_file_name' => $originalName,
            'imported_by'      => $importedById,
        ]);

        foreach ($parsed->items as $item) {
            SalesOrderItem::create([
                'order_id'         => $order->id,
                'product_id'       => $productIds[$item->sku],
                'line_no'          => $item->lineNo,
                'product_name_raw' => $item->name,
                'unit_raw'         => $item->unit,
                'requested_qty'    => $item->requestedQty,
                'actual_qty'       => null,
            ]);
        }

        return ['itemsCount' => count($parsed->items), 'newProducts' => $newProducts];
    }
}
