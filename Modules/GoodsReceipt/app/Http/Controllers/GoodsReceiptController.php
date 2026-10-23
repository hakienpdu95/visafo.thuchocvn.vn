<?php

namespace Modules\GoodsReceipt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ManualRefIdGenerator;
use App\Support\ProductNameMismatch;
use Illuminate\Http\RedirectResponse;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\GoodsReceipt\Actions\Backend\CreateGoodsReceiptAction;
use Modules\GoodsReceipt\Actions\Backend\DeleteGoodsReceiptAction;
use Modules\GoodsReceipt\Actions\Backend\UpdateGoodsReceiptAction;
use Modules\GoodsReceipt\Http\Controllers\Api\GoodsReceiptOptionApiController;
use Modules\GoodsReceipt\Http\Requests\SaveGoodsReceiptRequest;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\GoodsReceipt\Queries\GetGoodsReceiptHandler;
use Modules\GoodsReceipt\Queries\GetGoodsReceiptQuery;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Models\Product;
use Modules\Vendor\Models\Vendor;

class GoodsReceiptController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(GoodsReceipt::class, 'goods_receipt');
    }

    public function index()
    {
        $vendors = Vendor::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($v) => ['value' => $v->id, 'text' => $v->name])
            ->all();

        return view('goodsreceipt::goods-receipts.index', compact('vendors'));
    }

    public function show(GoodsReceipt $goodsReceipt, GetGoodsReceiptHandler $handler)
    {
        $goodsReceipt = $handler->handle(new GetGoodsReceiptQuery($goodsReceipt));

        // Lô canh tác chọn được cho từng sản phẩm của phiếu (một query cho cả phiếu)
        $farmingBatchOptions = ProductBatch::farmingBatchCandidates($goodsReceipt->vendor_id, $goodsReceipt->batches->pluck('product_id')->all())
            ->get()
            ->groupBy(fn ($fb) => $fb->partnerProduct?->product_id)
            ->map(fn ($group) => $group->map(fn ($fb) => [
                'value' => $fb->id,
                'text'  => $fb->batch_code
                    . ($fb->farmingSource ? ' · ' . $fb->farmingSource->name : '')
                    . ($fb->actual_harvest_date ? ' · thu hoạch ' . $fb->actual_harvest_date->format('d/m/Y') : ' · chưa thu hoạch'),
            ])->values());

        $documentTypes = DocumentMasterType::query()->applicableTo('goods_receipt')->orderBy('name')->get(['id', 'name', 'has_issue_place']);
        $batchMorph = (new ProductBatch())->getMorphClass();
        $documents = ComplianceDocument::query()
            ->with(['documentType', 'media', 'documentable'])
            ->where(fn ($q) => $q->where(fn ($r) => $r->where('documentable_type', $goodsReceipt->getMorphClass())->where('documentable_id', $goodsReceipt->id))
                ->orWhere(fn ($b) => $b->where('documentable_type', $batchMorph)->whereIn('documentable_id', $goodsReceipt->batches->pluck('id'))))
            ->latest()
            ->get();

        return view('goodsreceipt::goods-receipts.show', compact('goodsReceipt', 'farmingBatchOptions', 'documentTypes', 'documents'));
    }

    public function create(ManualRefIdGenerator $refIds)
    {
        return view('goodsreceipt::goods-receipts.form', [
            'goodsReceipt' => null,
            'formData'     => $this->formData(null, $refIds->next(GoodsReceipt::class, GoodsReceipt::MANUAL_REF_PREFIX)),
        ]);
    }

    public function store(SaveGoodsReceiptRequest $request, CreateGoodsReceiptAction $action, ManualRefIdGenerator $refIds): RedirectResponse
    {
        $attributes = $request->receiptAttributes();
        $attributes['misa_ref_id'] ??= $refIds->next(GoodsReceipt::class, GoodsReceipt::MANUAL_REF_PREFIX);

        $receipt = $action->handle([
            ...$attributes,
            'supplier_name'    => Vendor::query()->whereKey($attributes['vendor_id'])->value('name'),
            'source_file_name' => null,
            'imported_by'      => $request->user()?->id,
        ], $request->receiptItems());

        return redirect()->route('backend.goods-receipts.show', $receipt)
            ->with('success', 'Đã tạo phiếu nhập kho ' . $receipt->misa_ref_id . ' và sinh lô hàng.');
    }

    public function edit(GoodsReceipt $goodsReceipt)
    {
        if (! $goodsReceipt->isEditable()) {
            return redirect()->route('backend.goods-receipts.show', $goodsReceipt)
                ->withErrors(['receipt' => 'Lô của phiếu đã được in tem, kiểm duyệt truy xuất hoặc ghi QC — không thể sửa.']);
        }

        $goodsReceipt->load(['vendor', 'items.product']);

        return view('goodsreceipt::goods-receipts.form', [
            'goodsReceipt' => $goodsReceipt,
            'formData'     => $this->formData($goodsReceipt, $goodsReceipt->misa_ref_id),
        ]);
    }

    public function update(SaveGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt, UpdateGoodsReceiptAction $action): RedirectResponse
    {
        $attributes = $request->receiptAttributes();

        if (! $goodsReceipt->isManual() || $attributes['misa_ref_id'] === null) {
            $attributes['misa_ref_id'] = $goodsReceipt->misa_ref_id;
        }

        if ($goodsReceipt->isManual()) {
            $attributes['supplier_name'] = Vendor::query()->whereKey($attributes['vendor_id'])->value('name');
        }

        $action->handle($goodsReceipt, $attributes, $request->receiptItems());

        return redirect()->route('backend.goods-receipts.show', $goodsReceipt)
            ->with('success', 'Đã cập nhật phiếu nhập kho ' . $attributes['misa_ref_id'] . '.');
    }

    public function destroy(GoodsReceipt $goodsReceipt, DeleteGoodsReceiptAction $action): RedirectResponse
    {
        $action->handle($goodsReceipt);

        return redirect()->route('backend.goods-receipts.index')
            ->with('success', 'Đã xóa phiếu nhập kho ' . $goodsReceipt->misa_ref_id . ' cùng các lô hàng của phiếu.');
    }

    /** @return array<string, mixed> */
    private function formData(?GoodsReceipt $receipt, string $defaultRefId): array
    {
        $oldItems = old('items');

        if (is_array($oldItems)) {
            $products = Product::query()
                ->whereIn('id', collect($oldItems)->pluck('product_id')->filter()->all())
                ->get(['id', 'sku', 'name', 'unit'])
                ->keyBy('id');

            $rows = collect($oldItems)->map(fn ($row, $key) => [
                'uid'      => (string) $key,
                'product'  => ($p = $products->get($row['product_id'] ?? null)) ? GoodsReceiptOptionApiController::productOption($p) : null,
                'quantity' => $row['quantity'] ?? '',
            ])->values()->all();
        } else {
            $rows = ($receipt?->items ?? collect())
                ->sortBy('line_no')
                ->groupBy('product_id')
                ->map(fn ($group) => [
                    'uid'      => (string) $group->first()->id,
                    'product'  => $group->first()->product ? GoodsReceiptOptionApiController::productOption($group->first()->product) : null,
                    'quantity' => rtrim(rtrim(number_format((float) $group->sum('quantity'), 3, '.', ''), '0'), '.'),
                    'orig_product_id' => $group->first()->product_id,
                    'raw_name' => $group->map(fn ($i) => trim((string) $i->product_name_raw))
                        ->filter(fn ($n) => ProductNameMismatch::isSuspicious($group->first()->product?->name, $n))->unique()->implode('; ') ?: null,
                ])->values()->all();
        }

        $vendorId = old('vendor_id', $receipt?->vendor_id);
        $vendor = $vendorId
            ? ($receipt?->vendor?->id === $vendorId ? $receipt->vendor : Vendor::query()->find($vendorId))
            : null;

        return [
            'fields' => [
                'misa_ref_id'  => old('misa_ref_id', $defaultRefId),
                'vendor_id'    => $vendor?->id ?? '',
                'receipt_date' => old('receipt_date', $receipt?->receipt_date?->toDateString() ?? now()->toDateString()),
            ],
            'vendor' => $vendor ? GoodsReceiptOptionApiController::vendorOption($vendor) : null,
            'rows'   => $rows,
            'errors' => session('errors')?->getBag('default')->getMessages() ?? [],
            'urls'   => [
                'products' => route('backend.api.goods-receipts.product-options'),
                'vendors'  => route('backend.api.goods-receipts.vendor-options'),
            ],
        ];
    }
}
