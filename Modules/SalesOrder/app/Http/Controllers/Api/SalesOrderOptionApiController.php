<?php

namespace Modules\SalesOrder\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Product;
use App\Support\QuantityUnit;

class SalesOrderOptionApiController extends Controller
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

        return response()->json($products->map(fn (Product $p) => [
            'id'      => $p->id,
            'text'    => $p->name,
            'sku'     => $p->sku,
            'unit'    => $p->unit,
            'decimal' => QuantityUnit::allowsDecimal($p->unit),
        ]));
    }

    public function customers(Request $request): JsonResponse
    {
        $this->authorizeForm($request);

        $term = $this->term($request);

        $customers = Customer::query()
            ->where('status', CustomerStatus::Active->value)
            ->when($term, fn (Builder $q) => $q->where(fn (Builder $sub) => $sub
                ->where('name', 'like', "%$term%")
                ->orWhere('customer_code', 'like', "%$term%")
                ->orWhere('phone_number', 'like', "%$term%")))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'customer_code', 'name', 'address']);

        return response()->json($customers->map(fn (Customer $c) => [
            'id'      => $c->id,
            'text'    => $c->name,
            'code'    => $c->customer_code,
            'address' => $c->address,
        ]));
    }

    public function deliveryPoints(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeForm($request);

        return response()->json($customer->deliveryPoints()
            ->orderBy('site_name')
            ->get(['id', 'site_name', 'address', 'receiver_name', 'receiver_phone'])
            ->map(fn ($p) => [
                'id'            => $p->id,
                'text'          => $p->site_name ?: $p->address,
                'site_name'     => $p->site_name,
                'address'       => $p->address,
                'receiver_name' => $p->receiver_name,
            ]));
    }

    private function authorizeForm(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && ($user->can('sales_order.create') || $user->can('sales_order.update')), 403);
    }

    private function term(Request $request): ?string
    {
        $term = trim(mb_substr((string) $request->query('q', ''), 0, 100));

        return $term !== '' ? addcslashes($term, '%_\\') : null;
    }
}
