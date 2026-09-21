<?php

namespace Modules\FoodInspection\Actions\Backend;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Data\Requests\StoreFoodInspectionStep1Data;
use Modules\FoodInspection\Data\Requests\Step1DetailData;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;
use Modules\FoodInspection\Support\Step1RowAttributes;
use Modules\FoodInspection\Support\Step1VendorResolver;

class StoreFoodInspectionStep1Action
{
    use AsAction;

    public function handle(StoreFoodInspectionStep1Data $data, ?string $inspectedBy): FoodInspectionStep1Log
    {
        return DB::transaction(function () use ($data, $inspectedBy) {
            $customer = Customer::query()->findOrFail($data->customer_id);
            $details = collect($data->details)->values();

            $attachments = collect($data->attachments)
                ->map(fn (UploadedFile $f) => ['path' => $f->store('food-inspections', 'public'), 'name' => $f->getClientOriginalName()])
                ->all();

            $log = FoodInspectionStep1Log::create([
                'customer_id'         => $customer->id,
                'customer_name'       => $customer->name,
                'inspected_at'       => $data->inspected_at,
                'inspection_location' => $data->inspection_location,
                'attachments'        => $attachments ?: null,
                'note'               => $data->note,
                'failed_items_count' => $details->filter(fn (Step1DetailData $d) => $d->isFailed())->count(),
                'inspected_by'       => $inspectedBy,
            ]);

            $vendorIds = app(Step1VendorResolver::class)->resolve($details);

            foreach ($details as $i => $detail) {
                $log->details()->create(Step1RowAttributes::from($detail, $i + 1, $vendorIds[$i]));
            }

            return $log;
        });
    }
}
