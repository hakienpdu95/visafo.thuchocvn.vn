<?php

namespace Modules\Vendor\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Vendor\Models\Vendor;

class GetVendorHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Vendor
    {
        /** @var GetVendorQuery $query */
        $vendor = $query->vendor;

        $vendor->load(['certificates' => fn ($q) => $q->latest('issue_date')]);

        return $vendor;
    }
}
