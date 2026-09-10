<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\PartnerProduct;

class TraceabilityController extends Controller
{
    private const EXPIRING_SOON_DAYS = 30;

    public function index(Request $request): View
    {
        $customers = Customer::query()->orderBy('name')->get(['id', 'name', 'customer_code']);

        $selectedCustomer = null;
        $rows = collect();

        $customerId = $request->query('customer_id');

        if ($customerId) {
            $selectedCustomer = Customer::query()
                ->with(['products' => function ($query) {
                    $query->with(['partnerProducts' => function ($query) {
                        $query->with([
                            'vendor:id,name',
                            'activeCompliances' => fn ($query) => $query
                                ->with('documentType:id,name')
                                ->orderByDesc('issue_date'),
                        ]);
                    }]);
                }])
                ->find($customerId);

            if ($selectedCustomer) {
                $rows = $this->buildRows($selectedCustomer);
            }
        }

        return view('backend.traceability.index', [
            'customers'        => $customers,
            'selectedCustomer' => $selectedCustomer,
            'rows'             => $rows,
        ]);
    }

    private function buildRows(Customer $customer): Collection
    {
        $rows = collect();

        foreach ($customer->products as $product) {
            $partnerProducts = $product->partnerProducts;

            if ($partnerProducts->isEmpty()) {
                $rows->push([
                    'product'        => $product,
                    'rowspan'        => 1,
                    'isFirst'        => true,
                    'partnerProduct' => null,
                    'status'         => ['level' => 'red', 'label' => 'Chưa có NCC nguồn gốc'],
                ]);
                continue;
            }

            foreach ($partnerProducts as $index => $partnerProduct) {
                $rows->push([
                    'product'        => $product,
                    'rowspan'        => $partnerProducts->count(),
                    'isFirst'        => $index === 0,
                    'partnerProduct' => $partnerProduct,
                    'status'         => $this->complianceStatus($partnerProduct),
                ]);
            }
        }

        return $rows;
    }

    private function complianceStatus(PartnerProduct $partnerProduct): array
    {
        $compliances = $partnerProduct->activeCompliances;

        if ($compliances->isEmpty()) {
            return ['level' => 'red', 'label' => 'Thiếu hồ sơ', 'docs' => $compliances];
        }

        if ($compliances->contains(fn ($c) => $c->isExpired())) {
            return ['level' => 'red', 'label' => 'Hết hạn', 'docs' => $compliances];
        }

        if ($compliances->contains(fn ($c) => $c->isExpiringWithinDays(self::EXPIRING_SOON_DAYS))) {
            return ['level' => 'yellow', 'label' => 'Sắp hết hạn', 'docs' => $compliances];
        }

        return ['level' => 'green', 'label' => 'Đủ hồ sơ', 'docs' => $compliances];
    }
}
