<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Menu\Enums\MealTime;

class GetStep2FormOptionsHandler implements QueryHandlerInterface
{
    /** @return array{customers: array, locations: array<string, array<int, string>>, meals: array} */
    public function handle(QueryInterface $query): array
    {
        $customers = Customer::query()->where('status', CustomerStatus::Active)->with('deliveryPoints:id,customer_id,site_name')
            ->orderBy('name')->get(['id', 'name', 'address']);

        return [
            'customers' => $customers->map(fn (Customer $c) => ['value' => $c->id, 'text' => $c->name])->all(),
            // Gợi ý địa điểm cho combobox: tên các điểm giao/bếp ăn, kèm địa chỉ cơ sở nếu chưa khai báo điểm nào.
            'locations' => $customers->mapWithKeys(fn (Customer $c) => [$c->id => $c->deliveryPoints->pluck('site_name')
                ->when($c->address, fn ($col) => $col->push($c->address))->filter()->unique()->values()->all()])->all(),
            'meals'     => MealTime::options(),
        ];
    }
}
