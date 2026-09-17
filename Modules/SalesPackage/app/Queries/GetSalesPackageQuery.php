<?php

namespace Modules\SalesPackage\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\SalesPackage\Models\SalesPackage;

class GetSalesPackageQuery implements QueryInterface
{
    public function __construct(
        public readonly SalesPackage $package,
    ) {}
}
