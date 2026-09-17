<?php

namespace Modules\SalesPackage\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesPackage\Data\Requests\UpdateSalesPackageItemData;
use Modules\SalesPackage\Models\SalesPackageItem;

class UpdateSalesPackageItemAction
{
    use AsAction;

    public function handle(SalesPackageItem $item, UpdateSalesPackageItemData $data): SalesPackageItem
    {
        $item->update(['custom_name' => $data->custom_name]);

        if ($data->file !== null) {
            $item->clearMediaCollection('custom_document');
            $item->addMedia($data->file)->toMediaCollection('custom_document');
        }

        return $item->fresh();
    }
}
