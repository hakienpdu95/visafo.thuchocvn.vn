<?php

namespace Modules\Contract\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Contract\Data\Requests\StoreContractData;
use Modules\Contract\Models\Contract;

class StoreContractAction
{
    use AsAction;

    public function handle(StoreContractData $data): Contract
    {
        return Contract::create([
            'vendor_id'              => $data->vendor_id,
            'contract_type_id'       => $data->contract_type_id,
            'contract_number'        => $data->contract_number,
            'name'                   => $data->name,
            'total_value'            => $data->total_value,
            'start_date'             => $data->start_date,
            'end_date'               => $data->end_date,
            'is_auto_renew'          => $data->is_auto_renew,
            'renewal_period_months'  => $data->is_auto_renew ? $data->renewal_period_months : null,
            'status'                 => $data->status->value,
        ]);
    }
}
