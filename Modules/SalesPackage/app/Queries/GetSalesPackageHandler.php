<?php

namespace Modules\SalesPackage\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\SalesPackage\Models\SalesPackage;

class GetSalesPackageHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): SalesPackage
    {
        /** @var GetSalesPackageQuery $query */
        $package = $query->package;

        $package->load([
            'customer',
            'creator',
            'items.document.documentType',
            'items.document.media',
            'items.media',
        ]);

        return $package;
    }
}
