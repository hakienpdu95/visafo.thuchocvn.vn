<?php

namespace Modules\SalesPackage\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesPackage\Models\SalesPackageItem;

class DestroySalesPackageItemAction
{
    use AsAction;

    public function handle(SalesPackageItem $item): string
    {
        $name = $item->displayName() ?? 'Tài liệu bổ sung';
        $item->delete();

        return $name;
    }
}
