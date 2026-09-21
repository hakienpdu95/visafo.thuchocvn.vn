<?php

namespace Modules\Vendor\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Vendor\Enums\VendorStatus;
use Modules\Vendor\Models\Vendor;

class ListVendorOptionsHandler implements QueryHandlerInterface
{
    /** @return array<int, array{value: string, text: string}> */
    public function handle(QueryInterface $query): array
    {
        return Vendor::query()
            ->where('status', VendorStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Vendor $v) => ['value' => $v->id, 'text' => $v->name])
            ->all();
    }
}
