<?php

namespace Modules\SalesPackage\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Customer\Models\Customer;

class BuildPackageChecklistQuery implements QueryInterface
{
    public function __construct(
        public readonly Customer $customer,
    ) {}
}
