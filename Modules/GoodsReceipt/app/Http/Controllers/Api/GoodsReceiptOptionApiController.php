<?php

namespace Modules\GoodsReceipt\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\QuantityUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Product;
use Modules\Vendor\Enums\VendorStatus;
use Modules\Vendor\Models\Vendor;

class GoodsReceiptOptionApiController extends Controller
{
    private const LIMIT = 30;

    public function products(Request $request): JsonResponse
    {
        $this->authorizeForm($request);

        $term = $this->term($request);

        $products = Product::query()
            ->where('status', ProductStatus::Active->value)
            ->when($term, fn (Builder $q) => $q->where(fn (Builder $sub) => $sub
                ->where('name', 'like', "%$term%")
                ->orWhere('sku', 'like', "%$term%")))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'sku', 'name', 'unit']);

        return response()->json($products->map(fn (Product $p) => self::productOption($p)));
    }

    public function vendors(Request $request): JsonResponse
    {
        $this->authorizeForm($request);

        $term = $this->term($request);

        $vendors = Vendor::query()
            ->where('status', VendorStatus::Active->value)
            ->when($term, fn (Builder $q) => $q->where(fn (Builder $sub) => $sub
                ->where('name', 'like', "%$term%")
                ->orWhere('vendor_code', 'like', "%$term%")
                ->orWhere('tax_code', 'like', "%$term%")))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'vendor_code', 'name']);

        return response()->json($vendors->map(fn (Vendor $v) => self::vendorOption($v)));
    }

    /** @return array<string, mixed> */
    public static function productOption(Product $product): array
    {
        return [
            'id'      => $product->id,
            'text'    => $product->name,
            'sku'     => $product->sku,
            'unit'    => $product->unit,
            'decimal' => QuantityUnit::allowsDecimal($product->unit),
        ];
    }

    /** @return array<string, mixed> */
    public static function vendorOption(Vendor $vendor): array
    {
        return [
            'id'   => $vendor->id,
            'text' => $vendor->name,
            'code' => $vendor->vendor_code,
        ];
    }

    private function authorizeForm(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && ($user->can('goods_receipt.create') || $user->can('goods_receipt.update')), 403);
    }

    private function term(Request $request): ?string
    {
        $term = trim(mb_substr((string) $request->query('q', ''), 0, 100));

        return $term !== '' ? addcslashes($term, '%_\\') : null;
    }
}
