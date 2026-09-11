<?php

namespace Modules\Product\Actions\Backend;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreFarmingBatchData;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingSource;
use Modules\Product\Models\PartnerProduct;

class UpdateFarmingBatchAction
{
    use AsAction;

    public function handle(FarmingBatch $farmingBatch, StoreFarmingBatchData $data): FarmingBatch
    {
        $farmingSource = FarmingSource::query()->findOrFail($data->farming_source_id);
        $partnerProduct = PartnerProduct::query()->findOrFail($data->partner_product_id);

        if ($partnerProduct->vendor_id !== $farmingSource->vendor_id) {
            throw ValidationException::withMessages([
                'partner_product_id' => 'Mặt hàng phải thuộc đúng nông hộ của vùng trồng đã chọn.',
            ]);
        }

        $farmingBatch->update([
            'farming_source_id'      => $data->farming_source_id,
            'vendor_id'              => $farmingSource->vendor_id,
            'agri_seed_id'           => $data->agri_seed_id,
            'partner_product_id'     => $data->partner_product_id,
            'sowing_date'            => $data->sowing_date,
            'expected_harvest_date'  => $data->expected_harvest_date,
            'notes'                  => $data->notes,
        ]);

        return $farmingBatch;
    }
}
