<?php

namespace Modules\GoodsReceipt\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\GoodsReceipt\Queries\GetGoodsReceiptHandler;
use Modules\GoodsReceipt\Queries\GetGoodsReceiptQuery;
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

        return view('goodsreceipt::goods-receipts.show', compact('goodsReceipt', 'farmingBatchOptions'));
    }
}
