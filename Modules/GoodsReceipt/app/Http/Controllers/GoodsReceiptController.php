<?php

namespace Modules\GoodsReceipt\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\GoodsReceipt\Models\GoodsReceipt;
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

        return view('goodsreceipt::goods-receipts.show', compact('goodsReceipt'));
    }
}
