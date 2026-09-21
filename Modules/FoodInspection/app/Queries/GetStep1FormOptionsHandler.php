<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Vendor\Enums\VendorStatus;
use Modules\Vendor\Models\Vendor;

class GetStep1FormOptionsHandler implements QueryHandlerInterface
{
    /** @return array{vendors: array<int, array<string, ?string>>, customers: array<int, array<string, ?string>>} */
    public function handle(QueryInterface $query): array
    {
        $vendors = Vendor::query()
            ->where('status', VendorStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'phone_number'])
            ->map(fn (Vendor $v) => ['id' => $v->id, 'name' => $v->name, 'address' => $v->address, 'phone' => $v->phone_number])
            ->all();

        $customers = Customer::query()
            ->where('status', CustomerStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'address'])
            ->map(fn (Customer $c) => ['value' => $c->id, 'text' => $c->name, 'address' => $c->address])
            ->all();

        return ['vendors' => $vendors, 'customers' => $customers];
    }
}
