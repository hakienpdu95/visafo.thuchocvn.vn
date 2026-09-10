<?php

namespace Modules\Customer\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Customer\Models\Customer;

class GetCustomerHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Customer
    {
        /** @var GetCustomerQuery $query */
        $customer = $query->customer;

        $customer->load([
            'contacts' => fn ($q) => $q->latest(),
            'deliveryPoints' => fn ($q) => $q->latest(),
            'pic',
            'province',
            'ward',
        ]);

        return $customer;
    }
}
