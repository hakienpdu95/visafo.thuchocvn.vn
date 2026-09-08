<?php

namespace Modules\Vendor\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Vendor\Models\Vendor;

class GetVendorQuery implements QueryInterface
{
    public function __construct(
        public readonly Vendor $vendor,
    ) {}
}
