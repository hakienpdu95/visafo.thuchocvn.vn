<?php

namespace Modules\Menu\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Menu\Enums\MealTime;

class GetMenuFormOptionsHandler implements QueryHandlerInterface
{
    /** @return array{customers: array<int, array{value: string, text: string}>, meals: array<int, array{value: string, text: string}>} */
    public function handle(QueryInterface $query): array
    {
        return [
            'customers' => Customer::query()->where('status', CustomerStatus::Active)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Customer $c) => ['value' => $c->id, 'text' => $c->name])->all(),
            'meals'     => MealTime::options(),
        ];
    }
}
