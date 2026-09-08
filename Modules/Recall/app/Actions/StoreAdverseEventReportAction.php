<?php

namespace Modules\Recall\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recall\Data\Requests\StoreAdverseEventReportData;
use Modules\Recall\Enums\AdverseEventStatus;
use Modules\Recall\Models\AdverseEventReport;

class StoreAdverseEventReportAction
{
    use AsAction;

    public function handle(StoreAdverseEventReportData $data): AdverseEventReport
    {
        return AdverseEventReport::create([
            'product_id'                  => $data->product_id,
            'batch_id'                    => $data->batch_id,
            'lot_number_manual'           => $data->lot_number_manual,
            'mfg_or_exp_date_manual'      => $data->mfg_or_exp_date_manual,
            'company_name'                => $data->company_name,
            'company_address'             => $data->company_address,
            'reporter_name'               => $data->reporter_name,
            'reporter_title'              => $data->reporter_title,
            'reporter_phone'              => $data->reporter_phone,
            'reporter_fax'                => $data->reporter_fax,
            'reporter_email'              => $data->reporter_email,
            'ingredients_packaging'       => $data->ingredients_packaging,
            'product_form_purpose'        => $data->product_form_purpose,
            'manufacturer_origin'         => $data->manufacturer_origin,
            'consumer_name'               => $data->consumer_name,
            'consumer_id_number'          => $data->consumer_id_number,
            'consumer_age'                => $data->consumer_age,
            'consumer_gender'             => $data->consumer_gender?->value,
            'consumer_nationality'        => $data->consumer_nationality,
            'onset_at'                    => $data->onset_at,
            'reaction_description'        => $data->reaction_description,
            'time_since_last_use'         => $data->time_since_last_use,
            'usage_description'           => $data->usage_description,
            'was_hospitalized'            => $data->was_hospitalized,
            'required_medical_treatment'  => $data->required_medical_treatment,
            'outcome'                     => $data->outcome?->value,
            'outcome_date'                => $data->outcome_date,
            'report_source'               => $data->report_source?->value,
            'report_source_detail'        => $data->report_source_detail,
            'received_at'                 => $data->received_at,
            'status'                      => AdverseEventStatus::Draft->value,
        ]);
    }
}
