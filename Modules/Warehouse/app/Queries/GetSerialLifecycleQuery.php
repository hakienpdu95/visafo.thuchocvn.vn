<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\RetailItemTag;

class GetSerialLifecycleQuery implements QueryInterface
{
    public function __construct(
        public readonly RetailItemTag $tag,
    ) {}
}
